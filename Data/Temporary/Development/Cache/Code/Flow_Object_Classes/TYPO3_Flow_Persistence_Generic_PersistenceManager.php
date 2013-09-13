<?php
namespace TYPO3\Flow\Persistence\Generic;

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

/**
 * The generic Flow Persistence Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PersistenceManager_Original extends \TYPO3\Flow\Persistence\AbstractPersistenceManager {

	/**
	 * @var \SplObjectStorage
	 */
	protected $changedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * Create new instance
	 */
	public function __construct() {
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\Flow\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Injects the data mapper
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\Flow\Persistence\Generic\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
		$this->dataMapper->setPersistenceManager($this);
	}

	/**
	 * Injects the backend to use
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface $backend the backend to use for persistence
	 * @return void
	 * @Flow\Autowiring(false)
	 */
	public function injectBackend(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Session $persistenceSession The persistence session
	 * @return void
	 */
	public function injectPersistenceSession(\TYPO3\Flow\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Generic\Exception\MissingBackendException
	 */
	public function initialize() {
		if (!$this->backend instanceof \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface) throw new \TYPO3\Flow\Persistence\Generic\Exception\MissingBackendException('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$this->backend->setPersistenceManager($this);
		$this->backend->initialize($this->settings['backendOptions']);
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\Flow\Persistence\QueryInterface $query) {
		return $this->backend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\Flow\Persistence\QueryInterface $query) {
		return $this->backend->getObjectDataByQuery($query);
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
			// hand in only aggregate roots, leaving handling of subobjects to
			// the underlying storage layer
			// reconstituted entities must be fetched from the session and checked
			// for changes by the underlying backend as well!
		$this->backend->setAggregateRootObjects($this->addedObjects);
		$this->backend->setChangedEntities($this->changedObjects);
		$this->backend->setDeletedEntities($this->removedObjects);
		$this->backend->commit();

		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();

		$this->emitAllObjectsPersisted();
	}

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * Managed instances become detached, any fetches will
	 * return data directly from the persistence "backend".
	 * It will also forget about new objects.
	 *
	 * @return void
	 */
	public function clearState() {
		parent::clearState();
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();
		$this->persistenceSession->destroy();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the persistence session
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->persistenceSession->hasObject($object) === FALSE);
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return mixed The identifier for the object if it is known, or NULL
	 * @api
	 */
	public function getIdentifierByObject($object) {
		return $this->persistenceSession->getIdentifierByObject($object);
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading This option is ignored in this persistence manager
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		if (isset($this->newObjects[$identifier])) {
			return $this->newObjects[$identifier];
		}
		if ($this->persistenceSession->hasIdentifier($identifier)) {
			return $this->persistenceSession->getObjectByIdentifier($identifier);
		} else {
			$objectData = $this->backend->getObjectDataByIdentifier($identifier, $objectType);
			if ($objectData !== FALSE) {
				return $this->dataMapper->mapToObject($objectData);
			} else {
				return NULL;
			}
		}
	}

	/**
	 * Returns the object data for the (internal) identifier, if it is known to
	 * the backend. Otherwise FALSE is returned.
	 *
	 * @param string $identifier
	 * @param string $objectType
	 * @return object The object data for the identifier if it is known, or FALSE
	 */
	public function getObjectDataByIdentifier($identifier, $objectType = NULL) {
		return $this->backend->getObjectDataByIdentifier($identifier, $objectType);
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 */
	public function createQueryForType($type) {
		return $this->queryFactory->create($type);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->addedObjects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\Flow\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1249479819);
		}
		$this->changedObjects->attach($object);
	}

	/**
	 * Signals that all persistAll() has been executed successfully.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitAllObjectsPersisted() {
	}

	/**
	 * Tear down the persistence
	 *
	 * This method is called in functional tests to reset the storage between tests.
	 * The implementation is optional and depends on the underlying persistence backend.
	 *
	 * @return void
	 */
	public function tearDown() {
		if (method_exists($this->backend, 'tearDown')) {
			$this->backend->tearDown();
		}
	}

}
namespace TYPO3\Flow\Persistence\Generic;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The generic Flow Persistence Manager
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class PersistenceManager extends PersistenceManager_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\PersistenceManager', $this);
		parent::__construct();
		if ('TYPO3\Flow\Persistence\Generic\PersistenceManager' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 protected function Flow_Aop_Proxy_buildMethodsAndAdvicesArray() {
		if (method_exists(get_parent_class($this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;
		$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
			'getObjectByIdentifier' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', 'checkAccessAfterFetchingAnObjectByIdentifier', $objectManager, NULL),
				),
			),
			'emitAllObjectsPersisted' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\SignalSlot\SignalAspect', 'forwardSignalToDispatcher', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\PersistenceManager', $this);

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
		$result = NULL;
		if (method_exists(get_parent_class($this), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies() {
		if (!isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices) || empty($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices)) {
			$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
			if (is_callable('parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')) parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		}	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies() {
		if (!$this instanceof \Doctrine\ORM\Proxy\Proxy || isset($this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies)) {
			return;
		}
		$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies = TRUE;
		if (is_callable(array($this, 'Flow_Proxy_injectProperties'))) {
			$this->Flow_Proxy_injectProperties();
		}	}

	/**
	 * Autogenerated Proxy Method
	 */
	 private function Flow_Aop_Proxy_getAdviceChains($methodName) {
		$adviceChains = array();
		if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
			$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
		} else {
			if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
				$groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
				if (isset($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice'])) {
					$this->Flow_Aop_Proxy_groupedAdviceChains[$methodName]['TYPO3\Flow\Aop\Advice\AroundAdvice'] = new \TYPO3\Flow\Aop\Advice\AdviceChain($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice']);
					$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
				}
			}
		}
		return $adviceChains;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if (__CLASS__ !== $joinPoint->getClassName()) return parent::Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array('self', $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}
	}

	/**
	 * Autogenerated Proxy Method
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading This option is ignored in this persistence manager
	 * @return object The object for the identifier if it is known, or NULL
	 */
	 public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getObjectByIdentifier'])) {
		$result = parent::getObjectByIdentifier($identifier, $objectType, $useLazyLoading);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getObjectByIdentifier'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['identifier'] = $identifier;
				$methodArguments['objectType'] = $objectType;
				$methodArguments['useLazyLoading'] = $useLazyLoading;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getObjectByIdentifier');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Generic\PersistenceManager', 'getObjectByIdentifier', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getObjectByIdentifier']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getObjectByIdentifier']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitAllObjectsPersisted() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAllObjectsPersisted'])) {
		$result = parent::emitAllObjectsPersisted();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAllObjectsPersisted'] = TRUE;
			try {
			
					$methodArguments = array();

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Generic\PersistenceManager', 'emitAllObjectsPersisted', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitAllObjectsPersisted']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Generic\PersistenceManager', 'emitAllObjectsPersisted', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAllObjectsPersisted']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAllObjectsPersisted']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Generic\PersistenceManager');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Generic\PersistenceManager', $propertyName, 'transient')) continue;
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
		$this->injectQueryFactory(new \TYPO3\Flow\Persistence\Generic\QueryFactory());
		$this->injectDataMapper(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\Generic\DataMapper'));
		$this->injectPersistenceSession(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\Generic\Session'));
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
	}
}
#