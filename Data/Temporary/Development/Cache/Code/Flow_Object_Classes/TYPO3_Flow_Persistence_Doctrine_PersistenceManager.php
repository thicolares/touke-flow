<?php
namespace TYPO3\Flow\Persistence\Doctrine;

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
 * Flow's Doctrine PersistenceManager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PersistenceManager_Original extends \TYPO3\Flow\Persistence\AbstractPersistenceManager {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Initializes the persistence manager, called by Flow.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->entityManager->getEventManager()->addEventListener(array(\Doctrine\ORM\Events::onFlush), $this);
	}

	/**
	 * An onFlush event listener used to validate entities upon persistence.
	 *
	 * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
	 * @return void
	 */
	public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $eventArgs) {
		$unitOfWork = $this->entityManager->getUnitOfWork();
		$entityInsertions = $unitOfWork->getScheduledEntityInsertions();

		$validatedInstancesContainer = new \SplObjectStorage();
		$knownValueObjects = array();
		foreach ($entityInsertions as $entity) {
			if ($this->reflectionService->getClassSchema($entity)->getModelType() === \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
				$identifier = $this->getIdentifierByObject($entity);
				$className = $this->reflectionService->getClassNameByObject($entity);

				if (isset($knownValueObjects[$className][$identifier]) || $unitOfWork->getEntityPersister($className)->exists($entity)) {
					unset($entityInsertions[spl_object_hash($entity)]);
					continue;
				}

				$knownValueObjects[$className][$identifier] = TRUE;
			}
			$this->validateObject($entity, $validatedInstancesContainer);
		}

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($unitOfWork, 'entityInsertions', $entityInsertions, TRUE);

		foreach ($unitOfWork->getScheduledEntityUpdates() AS $entity) {
			$this->validateObject($entity, $validatedInstancesContainer);
		}
	}

	/**
	 * Validates the given object and throws an exception if validation fails.
	 *
	 * @param object $object
	 * @param \SplObjectStorage $validatedInstancesContainer
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\ObjectValidationFailedException
	 */
	protected function validateObject($object, \SplObjectStorage $validatedInstancesContainer) {
		$className = $this->entityManager->getClassMetadata(get_class($object))->getName();
		$validator = $this->validatorResolver->getBaseValidatorConjunction($className, array('Persistence', 'Default'));
		if ($validator === NULL) return;

		$validator->setValidatedInstancesContainer($validatedInstancesContainer);
		$validationResult = $validator->validate($object);
		if ($validationResult->hasErrors()) {
			$errorMessages = '';
			$allErrors = $validationResult->getFlattenedErrors();
			foreach ($allErrors as $path => $errors) {
				$errorMessages .= $path . ':' . PHP_EOL;
				foreach ($errors as $error) {
					$errorMessages .= (string)$error . PHP_EOL;
				}
			}
			throw new \TYPO3\Flow\Persistence\Exception\ObjectValidationFailedException('An instance of "' . get_class($object) . '" failed to pass validation with ' . count($errors) . ' error(s): ' . PHP_EOL . $errorMessages, 1322585164);
		}
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		if ($this->entityManager->isOpen()) {
			$this->entityManager->flush();
			$this->emitAllObjectsPersisted();
		} else {
			$this->systemLogger->log('persistAll() skipped flushing data, the Doctrine EntityManager is closed. Check the logs for error message.', LOG_ERR);
		}
	}

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * Managed instances become detached, any fetches will
	 * return data directly from the persistence "backend".
	 *
	 * @return void
	 */
	public function clearState() {
		parent::clearState();
		$this->entityManager->clear();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->entityManager->getUnitOfWork()->getEntityState($object, \Doctrine\ORM\UnitOfWork::STATE_NEW) === \Doctrine\ORM\UnitOfWork::STATE_NEW);
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
	 * @todo improve try/catch block
	 */
	public function getIdentifierByObject($object) {
		if (property_exists($object, 'Persistence_Object_Identifier')) {
			$identifierCandidate = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', TRUE);
			if ($identifierCandidate !== NULL) {
				return $identifierCandidate;
			}
		}
		if ($this->entityManager->contains($object)) {
			try {
				return current($this->entityManager->getUnitOfWork()->getEntityIdentifier($object));
			} catch (\Doctrine\ORM\ORMException $e) {}
		}
		return NULL;
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
	 * @return object The object for the identifier if it is known, or NULL
	 * @throws \RuntimeException
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		if ($objectType === NULL) {
			throw new \RuntimeException('Using only the identifier is not supported by Doctrine 2. Give classname as well or use repository to query identifier.', 1296646103);
		}
		if (isset($this->newObjects[$identifier])) {
			return $this->newObjects[$identifier];
		}
		if ($useLazyLoading === TRUE) {
			return $this->entityManager->getReference($objectType, $identifier);
		} else {
			return $this->entityManager->find($objectType, $identifier);
		}
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\Flow\Persistence\Doctrine\Query
	 */
	public function createQueryForType($type) {
		return new \TYPO3\Flow\Persistence\Doctrine\Query($type);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\KnownObjectException if the given $object is not new
	 * @throws \TYPO3\Flow\Persistence\Exception if another error occurs
	 * @api
	 */
	public function add($object) {
		if (!$this->isNewObject($object)) {
			throw new \TYPO3\Flow\Persistence\Exception\KnownObjectException('The object of type "' . get_class($object) . '" (identifier: "' . $this->getIdentifierByObject($object) . '") which was passed to EntityManager->add() is not a new object. Check the code which adds this entity to the repository and make sure that only objects are added which were not persisted before. Alternatively use update() for updating existing objects."', 1337934295);
		} else {
			try {
				$this->entityManager->persist($object);
			} catch (\Exception $exception) {
				throw new \TYPO3\Flow\Persistence\Exception('Could not add object of type "' . get_class($object) . '"', 1337934455, $exception);
			}
		}
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		$this->entityManager->remove($object);
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException if the given $object is new
	 * @throws \TYPO3\Flow\Persistence\Exception if another error occurs
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\Flow\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" (identifier: "' . $this->getIdentifierByObject($object) . '") which was passed to EntityManager->update() is not a previously persisted object. Check the code which updates this entity and make sure that only objects are updated which were persisted before. Alternatively use add() for persisting new objects."', 1313663277);
		}
		try {
			$this->entityManager->persist($object);
		} catch (\Exception $exception) {
			throw new \TYPO3\Flow\Persistence\Exception('Could not merge object of type "' . get_class($object) . '"', 1297778180, $exception);
		}
	}

	/**
	 * Called from functional tests, creates/updates database tables and compiles proxies.
	 *
	 * @return boolean
	 */
	public function compile() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
			if ($this->settings['backendOptions']['driver'] === 'pdo_sqlite') {
				$schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
			} else {
				$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
			}

			$proxyFactory = $this->entityManager->getProxyFactory();
			$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());

			$this->systemLogger->log('Doctrine 2 setup finished');
			return TRUE;
		} else {
			$this->systemLogger->log('Doctrine 2 setup skipped, driver and path backend options not set!', LOG_NOTICE);
			return FALSE;
		}
	}

	/**
	 * Called after a functional test in Flow, dumps everything in the database.
	 *
	 * @return void
	 */
	public function tearDown() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->entityManager->clear();

			$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
			$schemaTool->dropDatabase();
			$this->systemLogger->log('Doctrine 2 schema destroyed.', LOG_NOTICE);
		} else {
			$this->systemLogger->log('Doctrine 2 destroy skipped, driver and path backend options not set!', LOG_NOTICE);
		}
	}

	/**
	 * Signals that all persistAll() has been executed successfully.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitAllObjectsPersisted() {
	}

}

namespace TYPO3\Flow\Persistence\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Flow's Doctrine PersistenceManager
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
		if (get_class($this) === 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Doctrine\PersistenceManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface', $this);
		if ('TYPO3\Flow\Persistence\Doctrine\PersistenceManager' === get_class($this)) {
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
		if (get_class($this) === 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Doctrine\PersistenceManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface', $this);

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
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
	 * @return object The object for the identifier if it is known, or NULL
	 * @throws \RuntimeException
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
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', 'getObjectByIdentifier', $methodArguments, $adviceChain);
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

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', 'emitAllObjectsPersisted', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitAllObjectsPersisted']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', 'emitAllObjectsPersisted', $joinPoint->getMethodArguments(), NULL, $result);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Doctrine\PersistenceManager');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Doctrine\PersistenceManager', $propertyName, 'transient')) continue;
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
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$systemLogger_reference = &$this->systemLogger;
		$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SystemLoggerInterface');
		if ($this->systemLogger === NULL) {
			$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('6d57d95a1c3cd7528e3e6ea15012dac8', $systemLogger_reference);
			if ($this->systemLogger === NULL) {
				$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('6d57d95a1c3cd7528e3e6ea15012dac8',  $systemLogger_reference, 'TYPO3\Flow\Log\SystemLoggerInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'); });
			}
		}
		$entityManager_reference = &$this->entityManager;
		$this->entityManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('Doctrine\Common\Persistence\ObjectManager');
		if ($this->entityManager === NULL) {
			$this->entityManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('ea59127cf49656654065ffe160cf78e1', $entityManager_reference);
			if ($this->entityManager === NULL) {
				$this->entityManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('ea59127cf49656654065ffe160cf78e1',  $entityManager_reference, 'Doctrine\Common\Persistence\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('Doctrine\Common\Persistence\ObjectManager'); });
			}
		}
		$validatorResolver_reference = &$this->validatorResolver;
		$this->validatorResolver = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Validation\ValidatorResolver');
		if ($this->validatorResolver === NULL) {
			$this->validatorResolver = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('b457db29305ddeae13b61d92da000ca0', $validatorResolver_reference);
			if ($this->validatorResolver === NULL) {
				$this->validatorResolver = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('b457db29305ddeae13b61d92da000ca0',  $validatorResolver_reference, 'TYPO3\Flow\Validation\ValidatorResolver', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Validation\ValidatorResolver'); });
			}
		}
		$reflectionService_reference = &$this->reflectionService;
		$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\ReflectionService');
		if ($this->reflectionService === NULL) {
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('921ad637f16d2059757a908fceaf7076', $reflectionService_reference);
			if ($this->reflectionService === NULL) {
				$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('921ad637f16d2059757a908fceaf7076',  $reflectionService_reference, 'TYPO3\Flow\Reflection\ReflectionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'); });
			}
		}
	}
}
#