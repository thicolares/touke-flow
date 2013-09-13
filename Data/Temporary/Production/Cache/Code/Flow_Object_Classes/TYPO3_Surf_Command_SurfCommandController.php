<?php
namespace TYPO3\Surf\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Surf command controller
 */
class SurfCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\DeploymentService
	 */
	protected $deploymentService;

	/**
	 * List deployments
	 *
	 * List available deployments that can be deployed with the surf:deploy command.
	 *
	 * @param boolean $quiet If set, only the deployment names will be output, one per line
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 */
	public function listCommand($quiet = FALSE, $configurationPath = NULL) {
		$deploymentNames = $this->deploymentService->getDeploymentNames($configurationPath);

		if (!$quiet) {
			$this->outputLine('<u>Deployments</u>:' . PHP_EOL);
		}

		foreach ($deploymentNames as $deploymentName) {
			$line = $deploymentName;
			if (!$quiet) {
				$line = '  ' . $line;
			}
			$this->outputLine($line);
		}
	}

	/**
	 * Run a deployment
	 *
	 * @param string $deploymentName The deployment name
	 * @param boolean $verbose In verbose mode, the log output of the default logger will contain debug messages
	 * @param boolean $disableAnsi Disable ANSI formatting of output
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 */
	public function deployCommand($deploymentName, $verbose = FALSE, $disableAnsi = FALSE, $configurationPath = NULL) {
		$deployment = $this->deploymentService->getDeployment($deploymentName, $configurationPath);
		if ($deployment->getLogger() === NULL) {
			$logger = $this->createDefaultLogger($deploymentName, $verbose ? LOG_DEBUG : LOG_INFO, $disableAnsi);
			$deployment->setLogger($logger);
		}
		$deployment->initialize();

		$deployment->deploy();
		$this->response->setExitCode($deployment->getStatus());
	}

	/**
	 * Create a default logger with console and file backend
	 *
	 * @param string $deploymentName
	 * @param integer $severityThreshold
	 * @param boolean $disableAnsi
	 * @param boolean $addFileBackend
	 * @return \TYPO3\Flow\Log\Logger
	 */
	public function createDefaultLogger($deploymentName, $severityThreshold, $disableAnsi = FALSE, $addFileBackend = TRUE) {
		$logger = new \TYPO3\Flow\Log\Logger();
		$console = new \TYPO3\Surf\Log\Backend\AnsiConsoleBackend(array(
			'severityThreshold' => $severityThreshold,
			'disableAnsi' => $disableAnsi
		));
		$logger->addBackend($console);
		if ($addFileBackend) {
			$file = new \TYPO3\Flow\Log\Backend\FileBackend(array(
				'logFileURL' => FLOW_PATH_DATA . 'Logs/Surf-' . $deploymentName . '.log',
				'createParentDirectories' => TRUE,
				'severityThreshold' => LOG_DEBUG,
				'logMessageOrigin' => FALSE
			));
			$logger->addBackend($file);
		}
		return $logger;
	}

	/**
	 * Describe a deployment
	 *
	 * @param string $deploymentName
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 */
	public function describeCommand($deploymentName, $configurationPath = NULL) {
		$deployment = $this->deploymentService->getDeployment($deploymentName, $configurationPath);

		$this->outputLine('<em> Deployment <b>' . $deployment->getName() . ' </b></em>');
		$this->outputLine();
		$this->outputLine('<u>Workflow</u>: ' . $deployment->getWorkflow()->getName() . PHP_EOL);
		$this->outputLine('<u>Nodes</u>:' . PHP_EOL);
		foreach ($deployment->getNodes() as $node) {
			$this->outputLine('  <b>' . $node->getName() . '</b> (' . $node->getHostname() . ')');
		}
		$this->outputLine(PHP_EOL . '<u>Applications</u>:' . PHP_EOL);
		foreach ($deployment->getApplications() as $application) {
			$this->outputLine('  <b>' . $application->getName() . '</b>' . PHP_EOL);
			$this->outputLine('    <u>Deployment path</u>: ' . $application->getDeploymentPath());
			$this->outputLine('    <u>Options</u>: ');
			foreach ($application->getOptions() as $key => $value) {
				$this->outputLine('      ' . $key . ' => ' . $value);
			}
			$this->outputLine('    <u>Nodes</u>: ' . implode(', ', $application->getNodes()));
		}
	}

	/**
	 * Simulate a deployment
	 *
	 * @param string $deploymentName The deployment name
	 * @param boolean $verbose In verbose mode, the log output of the default logger will contain debug messages
	 * @param boolean $disableAnsi Disable ANSI formatting of output
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 */
	public function simulateCommand($deploymentName, $verbose = FALSE, $disableAnsi = FALSE, $configurationPath = NULL) {
		$deployment = $this->deploymentService->getDeployment($deploymentName, $configurationPath);
		if ($deployment->getLogger() === NULL) {
			$logger = $this->createDefaultLogger($deploymentName, $verbose ? LOG_DEBUG : LOG_INFO, $disableAnsi, FALSE);
			$deployment->setLogger($logger);
		}
		$deployment->initialize();

		$deployment->simulate();
	}

}
namespace TYPO3\Surf\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Surf command controller
 */
class SurfCommandController extends SurfCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Surf\Command\SurfCommandController' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Command\SurfCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Command\SurfCommandController', $propertyName, 'transient')) continue;
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
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$this->deploymentService = new \TYPO3\Surf\Domain\Service\DeploymentService();
	}
}
#