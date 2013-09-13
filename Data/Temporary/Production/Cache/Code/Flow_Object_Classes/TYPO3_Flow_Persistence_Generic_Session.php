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
 * The persistence session - acts as a UoW and Identity Map for Flow's
 * persistence framework.
 *
 * @Flow\Scope("singleton")
 */
class Session_Original {

	/**
	 * Reconstituted objects
	 *
	 * @var \SplObjectStorage
	 */
	protected $reconstitutedEntities;

	/**
	 * Reconstituted entity data (effectively their clean state)
	 *
	 * @var array
	 */
	protected $reconstitutedEntitiesData = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $identifierMap = array();

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Constructs a new Session
	 *
	 */
	public function __construct() {
		$this->reconstitutedEntities = new \SplObjectStorage();
		$this->objectMap = new \SplObjectStorage();
	}

	/**
	 * Injects a Reflection Service instance
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Registers data for a reconstituted object.
	 *
	 * $entityData format is described in
	 * "Documentation/PersistenceFramework object data format.txt"
	 *
	 * @param object $entity
	 * @param array $entityData
	 * @return void
	 */
	public function registerReconstitutedEntity($entity, array $entityData) {
		$this->reconstitutedEntities->attach($entity);
		$this->reconstitutedEntitiesData[$entityData['identifier']] = $entityData;
	}

	/**
	 * Replace a reconstituted object, leaves the clean data unchanged.
	 *
	 * @param object $oldEntity
	 * @param object $newEntity
	 * @return void
	 */
	public function replaceReconstitutedEntity($oldEntity, $newEntity) {
		$this->reconstitutedEntities->detach($oldEntity);
		$this->reconstitutedEntities->attach($newEntity);
	}

	/**
	 * Unregisters data for a reconstituted object
	 *
	 * @param object $entity
	 * @return void
	 */
	public function unregisterReconstitutedEntity($entity) {
		if ($this->reconstitutedEntities->contains($entity)) {
			$this->reconstitutedEntities->detach($entity);
			unset($this->reconstitutedEntitiesData[$this->getIdentifierByObject($entity)]);
		}
	}

	/**
	 * Returns all objects which have been registered as reconstituted
	 *
	 * @return \SplObjectStorage All reconstituted objects
	 */
	public function getReconstitutedEntities() {
		return $this->reconstitutedEntities;
	}

	/**
	 * Tells whether the given object is a reconstituted entity.
	 *
	 * @param object $entity
	 * @return boolean
	 */
	public function isReconstitutedEntity($entity) {
		return $this->reconstitutedEntities->contains($entity);
	}

	/**
	 * Checks whether the given property was changed in the object since it was
	 * reconstituted. Returns TRUE for unknown objects in all cases!
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return boolean
	 * @api
	 */
	public function isDirty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return TRUE;
		}

		if (property_exists($object, 'Flow_Persistence_LazyLoadingObject_thawProperties')) {
			return FALSE;
		}

		$currentValue = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		$cleanData =& $this->reconstitutedEntitiesData[$this->getIdentifierByObject($object)]['properties'][$propertyName];

		if ($currentValue instanceof \TYPO3\Flow\Persistence\Generic\LazySplObjectStorage && !$currentValue->isInitialized()
				|| ($currentValue === NULL && $cleanData['value'] === NULL)) {
			return FALSE;
		}

		if ($cleanData['multivalue']) {
			return $this->isMultiValuedPropertyDirty($cleanData, $currentValue);
		} else {
			return $this->isSingleValuedPropertyDirty($cleanData['type'], $cleanData['value'], $currentValue);
		}
	}

	/**
	 * Checks the $currentValue against the $cleanData.
	 *
	 * @param array $cleanData
	 * @param \Traversable $currentValue
	 * @return boolean
	 */
	protected function isMultiValuedPropertyDirty(array $cleanData, $currentValue) {
		if (count($cleanData['value']) > 0 && count($cleanData['value']) === count($currentValue)) {
			if ($currentValue instanceof \SplObjectStorage) {
				$cleanIdentifiers = array();
				foreach ($cleanData['value'] as &$cleanObjectData) {
					$cleanIdentifiers[] = $cleanObjectData['value']['identifier'];
				}
				sort($cleanIdentifiers);
				$currentIdentifiers = array();
				foreach ($currentValue as $currentObject) {
					$currentIdentifier = $this->getIdentifierByObject($currentObject);
					if ($currentIdentifier !== NULL) {
						$currentIdentifiers[] = $currentIdentifier;
					}
				}
				sort($currentIdentifiers);
				if ($cleanIdentifiers !== $currentIdentifiers) {
					return TRUE;
				}
			} else {
				foreach ($cleanData['value'] as &$cleanObjectData) {
					if (!isset($currentValue[$cleanObjectData['index']])) {
						return TRUE;
					}
					if (($cleanObjectData['type'] === 'array' && $this->isMultiValuedPropertyDirty($cleanObjectData, $currentValue[$cleanObjectData['index']]) === TRUE)
						|| ($cleanObjectData['type'] !== 'array' && $this->isSingleValuedPropertyDirty($cleanObjectData['type'], $cleanObjectData['value'], $currentValue[$cleanObjectData['index']]) === TRUE)) {
						return TRUE;
					}
				}
			}
		} elseif (count($cleanData['value']) > 0 || count($currentValue) > 0) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks the $previousValue against the $currentValue.
	 *
	 * @param string $type
	 * @param mixed $previousValue
	 * @param mixed &$currentValue
	 * @return boolean
	 */
	protected function isSingleValuedPropertyDirty($type, $previousValue, $currentValue) {
		switch ($type) {
			case 'integer':
				if ($currentValue === (int) $previousValue) return FALSE;
			break;
			case 'float':
				if ($currentValue === (float) $previousValue) return FALSE;
			break;
			case 'boolean':
				if ($currentValue === (boolean) $previousValue) return FALSE;
			break;
			case 'string':
				if ($currentValue === (string) $previousValue) return FALSE;
			break;
			case 'DateTime':
				if ($currentValue instanceof \DateTime && $currentValue->getTimestamp() === (int) $previousValue) return FALSE;
			break;
			default:
				if (is_object($currentValue) && $this->getIdentifierByObject($currentValue) === $previousValue['identifier']) return FALSE;
			break;
		}
		return TRUE;
	}

	/**
	 * Returns the previous (last persisted) state of the property.
	 * If nothing is found, NULL is returned.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getCleanStateOfProperty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return NULL;
		}
		$identifier = $this->getIdentifierByObject($object);
		if (!isset($this->reconstitutedEntitiesData[$identifier]['properties'][$propertyName])) {
			return NULL;
		}
		return $this->reconstitutedEntitiesData[$identifier]['properties'][$propertyName];
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @api
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given identifier is known to the identity map
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasIdentifier($identifier) {
		return array_key_exists($identifier, $this->identifierMap);
	}

	/**
	 * Returns the object for the given identifier
	 *
	 * @param string $identifier
	 * @return object
	 * @api
	 */
	public function getObjectByIdentifier($identifier) {
		return $this->identifierMap[$identifier];
	}

	/**
	 * Returns the identifier for the given object either from
	 * the session, if the object was registered, or from the object
	 * itself using a special uuid property or the internal
	 * properties set by AOP.
	 *
	 * Note: this returns an UUID even if the object has not been persisted
	 * in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return string
	 * @api
	 */
	public function getIdentifierByObject($object) {
		if ($this->hasObject($object)) {
			return $this->objectMap[$object];
		}

		$idPropertyNames = $this->reflectionService->getPropertyNamesByTag(get_class($object), 'id');
		if (count($idPropertyNames) === 1) {
			$idPropertyName = $idPropertyNames[0];
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $idPropertyName, TRUE);
		} elseif (property_exists($object, 'Persistence_Object_Identifier')) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', TRUE);
		}

		return NULL;
	}

	/**
	 * Register an identifier for an object
	 *
	 * @param object $object
	 * @param string $identifier
	 * @api
	 */
	public function registerObject($object, $identifier) {
		$this->objectMap[$object] = $identifier;
		$this->identifierMap[$identifier] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 */
	public function unregisterObject($object) {
		unset($this->identifierMap[$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

	/**
	 * Destroy the state of the persistence session and reset
	 * all internal data.
	 *
	 * @return void
	 */
	public function destroy() {
		$this->identifierMap = array();
		$this->objectMap = new \SplObjectStorage();
		$this->reconstitutedEntities = new \SplObjectStorage();
		$this->reconstitutedEntitiesData = array();
	}
}
namespace TYPO3\Flow\Persistence\Generic;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The persistence session - acts as a UoW and Identity Map for Flow's
 * persistence framework.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Session extends Session_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\Session') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\Session', $this);
		parent::__construct();
		if ('TYPO3\Flow\Persistence\Generic\Session' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\Session') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\Session', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Generic\Session');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Generic\Session', $propertyName, 'transient')) continue;
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
	}
}
#