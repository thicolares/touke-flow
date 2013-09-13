<?php
namespace TYPO3\Flow\Http\Client;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Client\InfiniteRedirectionException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * An HTTP client simulating a web browser
 *
 * @api
 */
class Browser_Original {

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $lastRequest;

	/**
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $lastResponse;

	/**
	 * If redirects should be followed
	 *
	 * @var boolean
	 */
	protected $followRedirects = TRUE;

	/**
	 * The very maximum amount of redirections to follow if there is
	 * a "Location" redirect (see also $redirectionStack property)
	 *
	 * @var integer
	 */
	protected $maximumRedirections = 10;

	/**
	 * A simple string array that keeps track of occurred "Location" header
	 * redirections to avoid infinite loops if the same redirection happens
	 *
	 * @var array
	 */
	protected $redirectionStack = array();

	/**
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * @var \TYPO3\Flow\Http\Client\RequestEngineInterface
	 */
	protected $requestEngine;

	/**
	 * Inject the request engine
	 *
	 * @param \TYPO3\Flow\Http\Client\RequestEngineInterface $requestEngine
	 * @return void
	 */
	public function setRequestEngine(RequestEngineInterface $requestEngine) {
		$this->requestEngine = $requestEngine;
	}

	/**
	 * Requests the given URI with the method and other parameters as specified.
	 * If a Location header was given and the status code is of response type 3xx
	 * (see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html, 14.30 Location)
	 *
	 * @param string|\TYPO3\Flow\Http\Uri $uri
	 * @param string $method
	 * @param array $arguments
	 * @param array $files
	 * @param array $server
	 * @param string $content
	 * @return \TYPO3\Flow\Http\Response The HTTP response
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 * @api
	 */
	public function request($uri, $method = 'GET', array $arguments = array(), array $files = array(), array $server = array(), $content = NULL) {
		if (is_string($uri)) {
			$uri = new Uri($uri);
		}
		if (!$uri instanceof Uri) {
			throw new \InvalidArgumentException('$uri must be a URI object or a valid string representation of a URI.', 1333443624);
		}

		$request = Request::create($uri, $method, $arguments, $this->cookies, $files, $server);
		if ($content !== NULL) {
			$request->setContent($content);
		}
		$response = $this->sendRequest($request);

		$location = $response->getHeader('Location');
		if ($this->followRedirects && $location !== NULL && $response->getStatusCode() >= 300 && $response->getStatusCode() <= 399) {
			if (in_array($location, $this->redirectionStack) || count($this->redirectionStack) >= $this->maximumRedirections) {
				throw new InfiniteRedirectionException('The Location "' . $location . '" to follow for a redirect will probably result into an infinite loop.', 1350391699);
			}
			$this->redirectionStack[] = $location;
			return $this->request($location);
		}
		$this->redirectionStack = array();
		return $response;
	}

	/**
	 * Sets a flag if redirects should be followed or not.
	 *
	 * @param boolean $flag
	 * @return void
	 */
	public function setFollowRedirects($flag) {
		$this->followRedirects = (boolean)$flag;
	}

	/**
	 * Sends a prepared request and returns the respective response.
	 *
	 * @param \TYPO3\Flow\Http\Request $request
	 * @return \TYPO3\Flow\Http\Response
	 * @api
	 */
	public function sendRequest(Request $request) {
		$this->lastRequest = $request;
		$this->lastResponse = $this->requestEngine->sendRequest($request);
		return $this->lastResponse;
	}

	/**
	 * Returns the response received after the last request.
	 *
	 * @return \TYPO3\Flow\Http\Response The HTTP response or NULL if there wasn't a response yet
	 * @api
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

	/**
	 * Returns the last request executed.
	 *
	 * @return \TYPO3\Flow\Http\Request The HTTP request or NULL if there wasn't a request yet
	 * @api
	 */
	public function getLastRequest() {
		return $this->lastRequest;
	}

	/**
	 * Returns the request engine used by this Browser.
	 *
	 * @return RequestEngineInterface
	 * @api
	 */
	public function getRequestEngine() {
		return $this->requestEngine;
	}

	/**
	 * Returns the DOM crawler which can be used to interact with the web page
	 * structure, submit forms, click links or fetch specific parts of the
	 * website's contents.
	 *
	 * The returned DOM crawler is bound to the response of the last executed
	 * request.
	 *
	 * @return \Symfony\Component\DomCrawler\Crawler
	 * @api
	 */
	public function getCrawler() {
		$crawler = new Crawler(NULL, $this->lastRequest->getBaseUri());
		$crawler->addContent($this->lastResponse->getContent(), $this->lastResponse->getHeader('Content-Type'));

		return $crawler;
	}

	/**
	 * Get the form specified by $xpath. If no $xpath given, return the first form
	 * on the page.
	 *
	 * @param string $xpath
	 * @return \Symfony\Component\DomCrawler\Form
	 * @api
	 */
	public function getForm($xpath = '//form') {
		return $this->getCrawler()->filterXPath($xpath)->form();
	}

	/**
	 * Submit a form
	 *
	 * @param \Symfony\Component\DomCrawler\Form $form
	 * @return \TYPO3\Flow\Http\Response
	 * @api
	 */
	public function submit(Form $form) {
		return $this->request($form->getUri(), $form->getMethod(), $form->getPhpValues(), $form->getPhpFiles());
	}
}

namespace TYPO3\Flow\Http\Client;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An HTTP client simulating a web browser
 */
class Browser extends Browser_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


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
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Http\Client\Browser');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Http\Client\Browser', $propertyName, 'transient')) continue;
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
}
#