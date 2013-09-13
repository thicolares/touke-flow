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
 * A data mapper to map raw records to objects
 *
 * @Flow\Scope("singleton")
 */
class DataMapper_Original {

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

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
	 * Injects a Reflection Service instance used for processing objects
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager The persistence manager
	 * @return void
	 */
	public function setPersistenceManager(\TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Maps the (aggregate root) node data and registers the objects as
	 * reconstituted with the session.
	 *
	 * Note: QueryResult relies on the fact that the first object of $objects has the numeric index "0"
	 *
	 * @param array $objectsData
	 * @return array
	 */
	public function mapToObjects(array $objectsData) {
		$objects = array();
		foreach ($objectsData as $objectData) {
			$objects[] = $this->mapToObject($objectData);
		}

		return $objects;
	}

	/**
	 * Maps a single record into the object it represents and registers it as
	 * reconstituted with the session.
	 *
	 * @param array $objectData
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Generic\Exception\InvalidObjectDataException
	 * @throws \TYPO3\Flow\Persistence\Exception
	 */
	public function mapToObject(array $objectData) {
		if ($objectData === array()) {
			throw new \TYPO3\Flow\Persistence\Generic\Exception\InvalidObjectDataException('The array with object data was empty, probably object not found or access denied.', 1277974338);
		}

		if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
			return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
		} else {
			$className = $objectData['classname'];
			$classSchema = $this->reflectionService->getClassSchema($className);

			$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
			$this->persistenceSession->registerObject($object, $objectData['identifier']);
			if ($classSchema->getModelType() === \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY) {
				$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
			}
			if ($objectData['properties'] === array()) {
				if (!$classSchema->isLazyLoadableObject()) {
					throw new \TYPO3\Flow\Persistence\Exception('The object of type "' . $className . '" is not marked as lazy loadable.', 1268309017);
				}
				$persistenceManager = $this->persistenceManager;
				$persistenceSession = $this->persistenceSession;
				$dataMapper = $this;
				$identifier = $objectData['identifier'];
				$modelType = $classSchema->getModelType();
				$object->Flow_Persistence_LazyLoadingObject_thawProperties = function ($object) use ($persistenceManager, $persistenceSession, $dataMapper, $identifier, $modelType) {
					$objectData = $persistenceManager->getObjectDataByIdentifier($identifier);
					$dataMapper->thawProperties($object, $identifier, $objectData);
					if ($modelType === \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY) {
						$persistenceSession->registerReconstitutedEntity($object, $objectData);
					}
				};
			} else {
				$this->thawProperties($object, $objectData['identifier'], $objectData);
			}

			return $object;
		}
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param object $object The object to set properties on
	 * @param string $identifier The identifier of the object
	 * @param array $objectData
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException
	 */
	public function thawProperties($object, $identifier, array $objectData) {
		$classSchema = $this->reflectionService->getClassSchema($objectData['classname']);

		foreach ($objectData['properties'] as $propertyName => $propertyData) {
			if (!$classSchema->hasProperty($propertyName)) continue;
			$propertyValue = NULL;

			if ($propertyData['value'] !== NULL) {
				switch ($propertyData['type']) {
					case 'integer':
						$propertyValue = (int) $propertyData['value'];
					break;
					case 'float':
						$propertyValue = (float) $propertyData['value'];
					break;
					case 'boolean':
						$propertyValue = (boolean) $propertyData['value'];
					break;
					case 'string':
						$propertyValue = (string) $propertyData['value'];
					break;
					case 'array':
						$propertyValue = $this->mapArray($propertyData['value']);
					break;
					case 'Doctrine\Common\Collections\Collection':
					case 'Doctrine\Common\Collections\ArrayCollection':
						$propertyValue = new \Doctrine\Common\Collections\ArrayCollection($this->mapArray($propertyData['value']));
					break;
					case 'SplObjectStorage':
						$propertyMetaData = $classSchema->getProperty($propertyName);
						$propertyValue = $this->mapSplObjectStorage($propertyData['value'], $propertyMetaData['lazy']);
					break;
					case 'DateTime':
						$propertyValue = $this->mapDateTime($propertyData['value']);
					break;
					default:
						if ($propertyData['value'] === FALSE) {
							throw new \TYPO3\Flow\Persistence\Exception\UnknownObjectException('An expected object was not found by the backend. It was expected for ' . $objectData['classname'] . '::' . $propertyName, 1289509867);
						}
						$propertyValue = $this->mapToObject($propertyData['value']);
					break;
				}
			} else {
				switch ($propertyData['type']) {
					case 'NULL':
						continue;
					break;
					case 'array':
						$propertyValue = $this->mapArray(NULL);
					break;
					case 'Doctrine\Common\Collections\Collection':
					case 'Doctrine\Common\Collections\ArrayCollection':
						$propertyValue = new \Doctrine\Common\Collections\ArrayCollection();
					break;
					case 'SplObjectStorage':
						$propertyValue = $this->mapSplObjectStorage(NULL);
					break;
				}
			}

			\TYPO3\Flow\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue, TRUE);
		}

		if (isset($objectData['metadata'])) {
			$object->Flow_Persistence_Metadata = $objectData['metadata'];
		}

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($object, 'Persistence_Object_Identifier', $identifier, TRUE);
	}

	/**
	 * Creates a \DateTime from an unix timestamp. If the input is not an integer
	 * NULL is returned.
	 *
	 * @param integer $timestamp
	 * @return \DateTime
	 */
	protected function mapDateTime($timestamp) {
		$datetime = new \DateTime();
		$datetime->setTimestamp((integer) $timestamp);
		return $datetime;
	}

	/**
	 * Maps an array proxy structure back to a native PHP array
	 *
	 * @param array $arrayValues
	 * @return array
	 */
	protected function mapArray(array $arrayValues = NULL) {
		if ($arrayValues === NULL) return array();

		$array = array();
		foreach ($arrayValues as $arrayValue) {
			if ($arrayValue['value'] === NULL) {
				$array[$arrayValue['index']] = NULL;
			} else {
				switch ($arrayValue['type']) {
					case 'integer':
						$array[$arrayValue['index']] = (int) $arrayValue['value'];
					break;
					case 'float':
						$array[$arrayValue['index']] = (float) $arrayValue['value'];
					break;
					case 'boolean':
						$array[$arrayValue['index']] = (boolean) $arrayValue['value'];
					break;
					case 'string':
						$array[$arrayValue['index']] = (string) $arrayValue['value'];
					break;
					case 'DateTime':
						$array[$arrayValue['index']] = $this->mapDateTime($arrayValue['value']);
					break;
					case 'array':
						$array[$arrayValue['index']] = $this->mapArray($arrayValue['value']);
					break;
					case 'SplObjectStorage':
						$array[$arrayValue['index']] = $this->mapSplObjectStorage($arrayValue['value']);
					break;
					default:
						$array[$arrayValue['index']] = $this->mapToObject($arrayValue['value']);
					break;
				}
			}
		}

		return $array;
	}

	/**
	 * Maps an SplObjectStorage proxy record back to an SplObjectStorage
	 *
	 * @param array $objectStorageValues
	 * @param boolean $createLazySplObjectStorage
	 * @return \SplObjectStorage
	 * @todo restore information attached to objects?
	 */
	protected function mapSplObjectStorage(array $objectStorageValues = NULL, $createLazySplObjectStorage = FALSE) {
		if ($objectStorageValues === NULL) return new \SplObjectStorage();

		if ($createLazySplObjectStorage) {
			$objectIdentifiers = array();
			foreach ($objectStorageValues as $arrayValue) {
				if ($arrayValue['value'] !== NULL) {
					$objectIdentifiers[] = $arrayValue['value']['identifier'];
				}
			}
			return new LazySplObjectStorage($objectIdentifiers);
		} else {
			$objectStorage = new \SplObjectStorage();

			foreach ($objectStorageValues as $arrayValue) {
				if ($arrayValue['value'] !== NULL) {
					$objectStorage->attach($this->mapToObject($arrayValue['value']));
				}
			}
			return $objectStorage;
		}
	}

}

namespace TYPO3\Flow\Persistence\Generic;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A data mapper to map raw records to objects
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class DataMapper extends DataMapper_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\DataMapper') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\DataMapper', $this);
		if ('TYPO3\Flow\Persistence\Generic\DataMapper' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Persistence\Generic\DataMapper') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Persistence\Generic\DataMapper', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Generic\DataMapper');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Generic\DataMapper', $propertyName, 'transient')) continue;
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
		$this->injectPersistenceSession(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\Generic\Session'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
	}
}
#