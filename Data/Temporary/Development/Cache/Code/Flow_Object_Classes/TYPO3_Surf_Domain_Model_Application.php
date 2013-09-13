<?php
namespace TYPO3\Surf\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * A generic application without any tasks
 *
 */
class Application_Original {

	/**
	 * The name
	 * @var string
	 */
	protected $name;

	/**
	 * The nodes for this application
	 * @var array
	 */
	protected $nodes = array();

	/**
	 * The deployment path for this application on a node
	 * @var string
	 */
	protected $deploymentPath;

	/**
	 * The options
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param string $name
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Register tasks for this application
	 *
	 * This is a template method that should be overriden by specific applications to define
	 * new task or to add tasks to the workflow.
	 *
	 * Example:
	 *
	 *   $workflow->addTask('typo3.surf:createdirectories', 'initialize', $this);
	 *
	 * @param \TYPO3\Surf\Domain\Model\Workflow $workflow
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @return void
	 */
	public function registerTasks(Workflow $workflow, Deployment $deployment) {}

	/**
	 * Get the application name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the application name
	 *
	 * @param string $name
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get the nodes where this application should be deployed
	 *
	 * @return array The application nodes
	 */
	public function getNodes() {
		return $this->nodes;
	}

	/**
	 * Set the nodes where this application should be deployed
	 *
	 * @param array $nodes The application nodes
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function setNodes(array $nodes) {
		$this->nodes = $nodes;
		return $this;
	}

	/**
	 * Add a node where this application should be deployed
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node The node to add
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function addNode(Node $node) {
		$this->nodes[$node->getName()] = $node;
		return $this;
	}

	/**
	 * Return TRUE if the given node is registered for this application
	 *
	 * @param Node $node The node to test
	 * @return boolean TRUE if the node is registered for this application
	 */
	public function hasNode(Node $node) {
		return isset($this->nodes[$node->getName()]);
	}

	/**
	 * Get the deployment path for this application
	 *
	 * This is the path for an application pointing to the root of the Surf deployment:
	 *
	 * [deploymentPath]
	 * |-- releases
	 * |-- cache
	 * |-- shared
	 *
	 * @return string The deployment path
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException If no deployment path was set
	 */
	public function getDeploymentPath() {
		/*
		 * FIXME Move check somewhere else
		 *
		if ($this->deploymentPath === NULL) {
			throw new InvalidConfigurationException(sprintf('No deployment path has been defined for application %s.', $this->name), 1312220645);
		}
		*/
		return $this->deploymentPath;
	}

	/**
	 * Get the path for shared resources for this application
	 *
	 * This path defaults to a directory "shared" below the deployment path.
	 *
	 * @return string The shared resources path
	 */
	public function getSharedPath() {
		return $this->getDeploymentPath() . '/shared';
	}

	/**
	 * Sets the deployment path
	 *
	 * @param string $deploymentPath The deployment path
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function setDeploymentPath($deploymentPath) {
		$this->deploymentPath = rtrim($deploymentPath, '/');
		return $this;
	}

	/**
	 * Get all options defined on this application instance
	 *
	 * The options will include the deploymentPath and sharedPath for
	 * unified option handling.
	 *
	 * @return array An array of options indexed by option key
	 */
	public function getOptions() {
		return array_merge($this->options, array(
			'deploymentPath' => $this->getDeploymentPath(),
			'sharedPath' => $this->getSharedPath()
		));
	}

	/**
	 * Get an option defined on this application instance
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getOption($key) {
		switch ($key) {
			case 'deploymentPath':
				return $this->deploymentPath;
			case 'sharedPath':
				return $this->getSharedPath();
			default:
				return $this->options[$key];
		}
	}

	/**
	 * Test if an option was set for this application
	 *
	 * @param string $key The option key
	 * @return boolean TRUE If the option was set
	 */
	public function hasOption($key) {
		return array_key_exists($key, $this->options);
	}

	/**
	 * Sets all options for this application instance
	 *
	 * @param array $options The options to set indexed by option key
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function setOptions($options) {
		$this->options = $options;
		return $this;
	}

	/**
	 * Set an option for this application instance
	 *
	 * @param string $key The option key
	 * @param mixed $value The option value
	 * @return \TYPO3\Surf\Domain\Model\Application The current instance for chaining
	 */
	public function setOption($key, $value) {
		$this->options[$key] = $value;
		return $this;
	}

}
namespace TYPO3\Surf\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A generic application without any tasks
 */
class Application extends Application_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param string $name
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $name in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
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
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Domain\Model\Application');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Domain\Model\Application', $propertyName, 'transient')) continue;
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