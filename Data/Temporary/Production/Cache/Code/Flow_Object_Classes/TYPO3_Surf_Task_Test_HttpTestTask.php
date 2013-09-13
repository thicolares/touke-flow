<?php
namespace TYPO3\Surf\Task\Test;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Exception\TaskExecutionException;

use TYPO3\Flow\Annotations as Flow;

/**
 * A task for testing HTTP request
 *
 * This task could be used to do smoke-tests against web applications in release (e.g. on a virtual host mounted
 * on the "next" symlink).
 */
class HttpTestTask_Original extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Execute this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		if (!isset($options['url'])) {
			throw new \TYPO3\Surf\Exception\InvalidConfigurationException('No url option provided for HttpTestTask', 1319534939);
		}

		// $this->logRequest($node, $application, $deployment, $options);

		if (isset($options['remote']) && $options['remote'] === TRUE) {
			$result = $this->executeRemoteCurlRequest(
				$options['url'],
				$node,
				$deployment,
				isset($options['additionalCurlParameters']) ? $options['additionalCurlParameters'] : ''
			);
		} else {
			$deployment->getLogger()->log('Requesting URL "' . $options['url'] . '"', LOG_DEBUG);
			$result = $this->executeLocalCurlRequest(
				$options['url'],
				isset($options['timeout']) ? $options['timeout'] : NULL,
				isset($options['port']) ? $options['port'] : NULL,
				isset($options['method']) ? $options['method'] : NULL,
				isset($options['username']) ? $options['username'] : NULL,
				isset($options['password']) ? $options['password'] : NULL,
				isset($options['data']) ? $options['data'] : NULL,
				isset($options['proxy']) ? $options['proxy'] : NULL,
				isset($options['proxyPort']) ? $options['proxyPort'] : NULL
			);
		}

		$this->assertExpectedStatus($options, $result);
		$this->assertExpectedHeaders($options, $result);
		$this->assertExpectedRegexp($options, $result);
	}

	/**
	 * @param array $options
	 * @param array $result
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function assertExpectedStatus(array $options, array $result) {
		if (!isset($options['expectedStatus'])) return;

		if ((int)$result['info']['http_code'] !== (int)$options['expectedStatus']) {
			throw new TaskExecutionException('Expected status code ' . $options['expectedStatus'] . ' but got ' . $result['info']['http_code'], 1319536619);
		}
	}

	/**
	 * @param array $options
	 * @param array $result
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function assertExpectedHeaders(array $options, array $result) {
		if (!isset($options['expectedHeaders'])) return;

		$expectedHeaders = array();
		$expectedHeadersConfiguration = $options['expectedHeaders'];
		if ($expectedHeadersConfiguration) {
			$configurationLines = explode(chr(10), $expectedHeadersConfiguration);
			foreach ($configurationLines as $configurationLine){
				list($headerName, $headerValue) = explode(':', $configurationLine, 2);
				$headerName = trim($headerName);
				$headerValue = trim($headerValue);
				if ($headerName && $headerValue) {
					$expectedHeaders[$headerName] = $headerValue;
				}
			}
		}

		if (count($expectedHeaders) > 0) {
			foreach ($expectedHeaders as $headerName => $expectedValue) {
				if (!isset($result['headers'][$headerName])) {
					throw new TaskExecutionException('Expected header "' . $headerName . '" not present', 1319535441);
				} else {
					$headerValue = $result['headers'][$headerName];
					$partialSuccess = $this->testSingleHeader($headerValue, $expectedValue);
					if (!$partialSuccess) {
						throw new TaskExecutionException('Expected header value for "' . $headerName . '" did not match "' . $expectedValue . '": "' . $headerValue . '"', 1319535733);
					}
				}
			}
		}
	}

	/**
	 * @param array $options
	 * @param array $result
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function assertExpectedRegexp(array $options, array $result) {
		if (!isset($options['expectedRegexp']))	return;

		$expectedRegexp = array();
		$expectedRegexpConfiguration = $options['expectedRegexp'];
		if ($expectedRegexpConfiguration) {
			$expectedRegexp = explode(chr(10), $expectedRegexpConfiguration);
		}

		if (count($expectedRegexp) > 0) {
			foreach ($expectedRegexp as $regexp) {
				$regexp = trim($regexp);
				if ($regexp !== '' && !preg_match($regexp, $result['body'])) {
					throw new TaskExecutionException('Body did not match expected regular expression "' . $regexp . '": ' . substr($result['body'], 0, 200) . (strlen($result['body']) > 200 ? '...' : ''), 1319536046);
				}
			}
		}
	}

	/**
	 * Compare returned HTTP headers with expected values
	 *
	 * @param string $headerValue
	 * @param string $expectedValue
	 * @return boolean
	 */
	protected function testSingleHeader($headerValue, $expectedValue) {
		if (!$headerValue || strlen(trim($headerValue)) === 0) {
			return FALSE;
		}

			// = Value equals
		if (strpos($expectedValue, '=') === 0) {
			$result = $headerValue === trim(substr($expectedValue, 1));
		}
			// < Intval smaller than
		else if (strpos($expectedValue, '<') === 0) {
			$result = intval($headerValue) < intval(substr($expectedValue, 1));
		}
			// > Intval bigger than
		else if (strpos($expectedValue, '>') === 0) {
			$result = intval($headerValue) > intval(substr($expectedValue, 1));
		}
			// Default
		else {
			$result = $headerValue === $expectedValue;
		}

		return $result;
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		// $this->logRequest($node, $application, $deployment, $options);
	}

	/**
	 * @param string $url Request URL
	 * @param integer $timeout Request HTTP timeout, defaults to 0 (no timeout)
	 * @param integer $port Request HTTP port
	 * @param string $method Request method, defaults to GET. POST, PUT and DELETE are also supported.
	 * @param string $username Optional username for HTTP authentication
	 * @param string $password Optional password for HTTP authentication
	 * @param string $data
	 * @param string $proxy
	 * @param integer $proxyPort
	 * @return array time in seconds and status information im associative arrays
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function executeLocalCurlRequest($url, $timeout = NULL, $port = NULL, $method = 'GET', $username = NULL, $password = NULL, $data = '', $proxy = NULL, $proxyPort = NULL) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		if ($timeout !== NULL) {
			curl_setopt($curl, CURLOPT_TIMEOUT, (int)ceil($timeout / 1000));
		}

		if ($username !== NULL && $password !== NULL) {
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
		}

		if ($port !== NULL) {
			curl_setopt($curl, CURLOPT_PORT, (int)$port);
		}

		if ($proxy !== NULL && $proxyPort !== NULL) {
			curl_setopt($curl, CURLOPT_PROXY, $proxy);
			curl_setopt($curl, CURLOPT_PROXYPORT, (int)$proxyPort);
		}

		switch ($method) {
			case 'POST':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case 'PUT':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if ($response === FALSE) {
			throw new TaskExecutionException('HTTP request did not return a response', 1334347427);
		}

		list($headerText, $body) = preg_split('/\n[\s]*\n/', $response, 2);
		$headers = $this->extractResponseHeaders($headerText);

		return array(
			'headers' => $headers,
			'body' => $body,
			'info' => $info
		);
	}

	/**
	 *
	 * @param string $url Request URL
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param string $additionalCurlParameters
	 * @return array time in seconds and status information im associative arrays
	 */
	protected function executeRemoteCurlRequest($url, Node $node, Deployment $deployment, $additionalCurlParameters = '') {
		$command = 'curl -s -I  ' . $additionalCurlParameters . ' ' . escapeshellarg($url);
		$head = $this->shell->execute($command, $node, $deployment, FALSE, FALSE);

		$command = 'curl -s ' . $additionalCurlParameters . ' ' . escapeshellarg($url);
		$body = $this->shell->execute($command, $node, $deployment, FALSE, FALSE);


		list($status, $headersString) = explode(chr(10), $head, 2);
		$statusParts = explode(' ', $status);
		$statusCode = $statusParts[1];
		$info = array(
			'http_code' => $statusCode
		);
		$headers = $this->extractResponseHeaders(trim($headersString));

		return array(
			'headers' => $headers,
			'body' => $body,
			'info' => $info
		);
	}


	/**
	 * Split response into headers and body part
	 *
	 * @param string $headerText
	 * @return array Extracted response headers as associative array
	 */
	protected function extractResponseHeaders($headerText) {
		$headers = array();
		if ($headerText) {
			$headerLines = explode(chr(10), $headerText);
			foreach ($headerLines as $headerLine) {
				$headerParts = explode(':', $headerLine, 2);
				if (count($headerParts) < 2) continue;

				$headerName = trim($headerParts[0]);
				$headerValue = trim($headerParts[1]);
				if ($headerName && $headerValue) {
					$headers[$headerName] = $headerValue;
				}
			}
		}
		return $headers;
	}

}
namespace TYPO3\Surf\Task\Test;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A task for testing HTTP request
 * 
 * This task could be used to do smoke-tests against web applications in release (e.g. on a virtual host mounted
 * on the "next" symlink).
 */
class HttpTestTask extends HttpTestTask_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Surf\Task\Test\HttpTestTask' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

	if (property_exists($this, 'Flow_Persistence_RelatedEntities') && is_array($this->Flow_Persistence_RelatedEntities)) {
		$persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		foreach ($this->Flow_Persistence_RelatedEntities as $entityInformation) {
			$entity = $persistenceManager->getObjectByIdentifier($entityInformation['identifier'], $entityInformation['entityType'], TRUE);
			if (isset($entityInformation['entityPath'])) {
				$this->$entityInformation['propertyName'] = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->$entityInformation['propertyName'], $entityInformation['entityPath'], $entity);
			} else {
				$this->$entityInformation['propertyName'] = $entity;
			}
		}
		unset($this->Flow_Persistence_RelatedEntities);
	}
				$this->Flow_Proxy_injectProperties();
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Task\Test\HttpTestTask');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Task\Test\HttpTestTask', $propertyName, 'transient')) continue;
		if (is_array($this->$propertyName) || (is_object($this->$propertyName) && ($this->$propertyName instanceof \ArrayObject || $this->$propertyName instanceof \SplObjectStorage ||$this->$propertyName instanceof \Doctrine\Common\Collections\Collection))) {
			foreach ($this->$propertyName as $key => $value) {
				$this->searchForEntitiesAndStoreIdentifierArray((string)$key, $value, $propertyName);
			}
		}
		if (is_object($this->$propertyName) && !$this->$propertyName instanceof \Doctrine\Common\Collections\Collection) {
			if ($this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
				$className = get_parent_class($this->$propertyName);
			} else {
				$className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($this->$propertyName));
			}
			if ($this->$propertyName instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->isNewObject($this->$propertyName) || $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
				if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
					$this->Flow_Persistence_RelatedEntities = array();
					$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
				}
				$identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getIdentifierByObject($this->$propertyName);
				if (!$identifier && $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
					$identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->$propertyName, '_identifier', TRUE));
				}
				$this->Flow_Persistence_RelatedEntities[$propertyName] = array(
					'propertyName' => $propertyName,
					'entityType' => $className,
					'identifier' => $identifier
				);
				continue;
			}
			if ($className !== FALSE && (\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getScope($className) === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SINGLETON || $className === 'TYPO3\Flow\Object\DependencyInjection\DependencyProxy')) {
				continue;
			}
		}
		$this->Flow_Object_PropertiesToSerialize[] = $propertyName;
	}
	$result = $this->Flow_Object_PropertiesToSerialize;
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 private function searchForEntitiesAndStoreIdentifierArray($path, $propertyValue, $originalPropertyName) {

		if (is_array($propertyValue) || (is_object($propertyValue) && ($propertyValue instanceof \ArrayObject || $propertyValue instanceof \SplObjectStorage))) {
			foreach ($propertyValue as $key => $value) {
				$this->searchForEntitiesAndStoreIdentifierArray($path . '.' . $key, $value, $originalPropertyName);
			}
		} elseif ($propertyValue instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->isNewObject($propertyValue) || $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
			if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
				$this->Flow_Persistence_RelatedEntities = array();
				$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
			}
			if ($propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
				$className = get_parent_class($propertyValue);
			} else {
				$className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
			}
			$identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getIdentifierByObject($propertyValue);
			if (!$identifier && $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
				$identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($propertyValue, '_identifier', TRUE));
			}
			$this->Flow_Persistence_RelatedEntities[$originalPropertyName . '.' . $path] = array(
				'propertyName' => $originalPropertyName,
				'entityType' => $className,
				'identifier' => $identifier,
				'entityPath' => $path
			);
			$this->$originalPropertyName = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->$originalPropertyName, $path, NULL);
		}
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 private function Flow_Proxy_injectProperties() {
		$this->shell = new \TYPO3\Surf\Domain\Service\ShellCommandService();
	}
}
#