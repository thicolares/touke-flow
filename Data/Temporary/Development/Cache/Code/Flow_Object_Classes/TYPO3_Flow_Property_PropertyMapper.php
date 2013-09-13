<?php
namespace TYPO3\Flow\Property;

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
use TYPO3\Flow\Utility\TypeHandling;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PropertyMapper_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
	 */
	protected $configurationBuilder;

	/**
	 * A multi-dimensional array which stores the Type Converters available in the system.
	 * It has the following structure:
	 * 1. Dimension: Source Type
	 * 2. Dimension: Target Type
	 * 3. Dimension: Priority
	 * Value: Type Converter instance
	 *
	 * @var array
	 */
	protected $typeConverters = array();

	/**
	 * A list of property mapping messages (errors, warnings) which have occured on last mapping.
	 * @var \TYPO3\Flow\Error\Result
	 */
	protected $messages;

	/**
	 * Lifecycle method, called after all dependencies have been injected.
	 * Here, the typeConverter array gets initialized.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException
	 */
	public function initializeObject() {
		$typeConverterClassNames = static::getTypeConverterImplementationClassNames($this->objectManager);
		foreach ($typeConverterClassNames as $typeConverterClassName) {
			$typeConverter = $this->objectManager->get($typeConverterClassName);
			foreach ($typeConverter->getSupportedSourceTypes() as $supportedSourceType) {
				if (isset($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()])) {
					throw new \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion from "' . $supportedSourceType . '" to "' . $typeConverter->getSupportedTargetType() . '" with priority "' . $typeConverter->getPriority() . '": ' . get_class($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()]) . ' and ' . get_class($typeConverter), 1297951378);
				}
				$this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] = $typeConverter;
			}
		}
	}

	/**
	 * Returns all class names implementing the TypeConverterInterface.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of type converter implementations
	 * @Flow\CompileStatic
	 */
	static public function getTypeConverterImplementationClassNames($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		return $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Property\TypeConverterInterface');
	}

	/**
	 * Map $source to $targetType, and return the result.
	 *
	 * If $source is an object and already is of type $targetType, we do return the unmodified object.
	 *
	 * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
	 * @param string $targetType The type of the target; can be either a class name or a simple type.
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
	 * @return mixed an instance of $targetType
	 * @throws \TYPO3\Flow\Property\Exception
	 * @api
	 */
	public function convert($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			$configuration = $this->configurationBuilder->build();
		}

		$currentPropertyPath = array();
		$this->messages = new \TYPO3\Flow\Error\Result();
		try {
			$result = $this->doMapping($source, $targetType, $configuration, $currentPropertyPath);
			if ($result instanceof \TYPO3\Flow\Error\Error) {
				return NULL;
			}

			return $result;
		} catch (\Exception $e) {
			throw new \TYPO3\Flow\Property\Exception('Exception while property mapping for target type "' . $targetType . '", at property path "' . implode('.', $currentPropertyPath) . '": ' . $e->getMessage(), 1297759968, $e);
		}
	}

	/**
	 * Get the messages of the last Property Mapping
	 *
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Internal function which actually does the property mapping.
	 *
	 * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
	 * @param string $targetType The type of the target; can be either a class name or a simple type.
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping.
	 * @param array $currentPropertyPath The property path currently being mapped; used for knowing the context in case an exception is thrown.
	 * @return mixed an instance of $targetType
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function doMapping($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration, &$currentPropertyPath) {
		if (is_object($source)) {
			$targetType = $this->parseCompositeType($targetType);
			if ($source instanceof $targetType) {
				return $source;
			}
		}

		if ($source === NULL) {
			$source = '';
		}

		$typeConverter = $this->findTypeConverter($source, $targetType, $configuration);
		$targetType = $typeConverter->getTargetTypeForSource($source, $targetType, $configuration);

		if (!is_object($typeConverter) || !($typeConverter instanceof \TYPO3\Flow\Property\TypeConverterInterface)) {
			throw new Exception\TypeConverterException('Type converter for "' . $source . '" -> "' . $targetType . '" not found.');
		}

		$convertedChildProperties = array();
		foreach ($typeConverter->getSourceChildPropertiesToBeConverted($source) as $sourcePropertyName => $sourcePropertyValue) {
			$targetPropertyName = $configuration->getTargetPropertyName($sourcePropertyName);
			if (!$configuration->shouldMap($targetPropertyName)) {
				throw new Exception\InvalidPropertyMappingConfigurationException('It is not allowed to map property "' . $targetPropertyName . '". You need to use $propertyMappingConfiguration->allowProperties(\'' . $targetPropertyName . '\') to enable mapping of this property.', 1335969887);
			}

			$targetPropertyType = $typeConverter->getTypeOfChildProperty($targetType, $targetPropertyName, $configuration);

			$subConfiguration = $configuration->getConfigurationFor($targetPropertyName);

			$currentPropertyPath[] = $targetPropertyName;
			$targetPropertyValue = $this->doMapping($sourcePropertyValue, $targetPropertyType, $subConfiguration, $currentPropertyPath);
			array_pop($currentPropertyPath);
			if (!($targetPropertyValue instanceof \TYPO3\Flow\Error\Error)) {
				$convertedChildProperties[$targetPropertyName] = $targetPropertyValue;
			}
		}
		$result = $typeConverter->convertFrom($source, $targetType, $convertedChildProperties, $configuration);

		if ($result instanceof \TYPO3\Flow\Error\Error) {
			$this->messages->forProperty(implode('.', $currentPropertyPath))->addError($result);
		}

		return $result;
	}

	/**
	 * Determine the type converter to be used. If no converter has been found, an exception is raised.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Flow\Property\TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	protected function findTypeConverter($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		if ($configuration->getTypeConverter() !== NULL) return $configuration->getTypeConverter();

		$sourceType = $this->determineSourceType($source);

		if (!is_string($targetType)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('The target type was no string, but of type "' . gettype($targetType) . '"', 1297941727);
		}
		$targetType = $this->parseCompositeType($targetType);
		$normalizedTargetType = TypeHandling::normalizeType($targetType);
		$converter = NULL;

		if (TypeHandling::isSimpleType($normalizedTargetType)) {
			if (isset($this->typeConverters[$sourceType][$normalizedTargetType])) {
				$converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$normalizedTargetType], $source, $normalizedTargetType);
			}
		} else {
			$converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $normalizedTargetType);
		}

		if ($converter === NULL) {
			throw new \TYPO3\Flow\Property\Exception\TypeConverterException('No converter found which can be used to convert from "' . $sourceType . '" to "' . $normalizedTargetType . '".');
		}

		return $converter;
	}

	/**
	 * Tries to find a suitable type converter for the given source and target type.
	 *
	 * @param string $source The actual source value
	 * @param string $sourceType Type of the source to convert from
	 * @param string $targetClass Name of the target class to find a type converter for
	 * @return mixed Either the matching object converter or NULL
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	protected function findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetClass) {
		if (!class_exists($targetClass) && !interface_exists($targetClass)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Could not find a suitable type converter for "' . $targetClass . '" because no such class or interface exists.', 1297948764);
		}

		if (!isset($this->typeConverters[$sourceType])) {
			return NULL;
		}

		$convertersForSource = $this->typeConverters[$sourceType];
		if (isset($convertersForSource[$targetClass])) {
			$converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass], $source, $targetClass);
			if ($converter !== NULL) {
				return $converter;
			}
		}

		foreach (class_parents($targetClass) as $parentClass) {
			if (!isset($convertersForSource[$parentClass])) continue;

			$converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$parentClass], $source, $targetClass);
			if ($converter !== NULL) {
				return $converter;
			}
		}

		$converters = $this->getConvertersForInterfaces($convertersForSource, class_implements($targetClass));
		$converter = $this->findEligibleConverterWithHighestPriority($converters, $source, $targetClass);

		if ($converter !== NULL) {
			return $converter;
		}
		if (isset($convertersForSource['object'])) {
			return $this->findEligibleConverterWithHighestPriority($convertersForSource['object'], $source, $targetClass);
		} else {
			return NULL;
		}
	}

	/**
	 * @param mixed $converters
	 * @param mixed $source
	 * @param string $targetType
	 * @return mixed Either the matching object converter or NULL
	 */
	protected function findEligibleConverterWithHighestPriority($converters, $source, $targetType) {
		if (!is_array($converters)) return NULL;
		krsort($converters);
		reset($converters);
		foreach ($converters as $converter) {
			if ($converter->canConvertFrom($source, $targetType)) {
				return $converter;
			}
		}
		return NULL;
	}

	/**
	 * @param array $convertersForSource
	 * @param array $interfaceNames
	 * @return array
	 * @throws \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException
	 */
	protected function getConvertersForInterfaces(array $convertersForSource, array $interfaceNames) {
		$convertersForInterface = array();
		foreach ($interfaceNames as $implementedInterface) {
			if (isset($convertersForSource[$implementedInterface])) {
				foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
					if (isset($convertersForInterface[$priority])) {
						throw new \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion to an interface with priority "' . $priority . '". ' . get_class($convertersForInterface[$priority]) . ' and ' . get_class($converter), 1297951338);
					}
					$convertersForInterface[$priority] = $converter;
				}
			}
		}
		return $convertersForInterface;
	}

	/**
	 * Determine the type of the source data, or throw an exception if source was an unsupported format.
	 *
	 * @param mixed $source
	 * @return string the type of $source
	 * @throws \TYPO3\Flow\Property\Exception\InvalidSourceException
	 */
	protected function determineSourceType($source) {
		if (is_string($source)) {
			return 'string';
		} elseif (is_array($source)) {
			return 'array';
		} elseif (is_float($source)) {
			return 'float';
		} elseif (is_integer($source)) {
			return 'integer';
		} elseif (is_bool($source)) {
			return 'boolean';
		} else {
			throw new \TYPO3\Flow\Property\Exception\InvalidSourceException('The source is not of type string, array, float, integer or boolean, but of type "' . gettype($source) . '"', 1297773150);
		}
	}

	/**
	 * Parse a composite type like \Foo\Collection<\Bar\Entity> into
	 * \Foo\Collection
	 *
	 * @param string $compositeType
	 * @return string
	 */
	public function parseCompositeType($compositeType) {
		if (strpos($compositeType, '<') !== FALSE) {
			$compositeType = substr($compositeType, 0, strpos($compositeType, '<'));
		}
		return $compositeType;
	}
}
namespace TYPO3\Flow\Property;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class PropertyMapper extends PropertyMapper_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Property\PropertyMapper') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\PropertyMapper', $this);
		if ('TYPO3\Flow\Property\PropertyMapper' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Property\PropertyMapper') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\PropertyMapper', $this);

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

		$this->initializeObject(2);
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Property\PropertyMapper');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Property\PropertyMapper', $propertyName, 'transient')) continue;
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
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
		$configurationBuilder_reference = &$this->configurationBuilder;
		$this->configurationBuilder = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Property\PropertyMappingConfigurationBuilder');
		if ($this->configurationBuilder === NULL) {
			$this->configurationBuilder = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('59cb6d934c9fe22d52baf9011a7b3a39', $configurationBuilder_reference);
			if ($this->configurationBuilder === NULL) {
				$this->configurationBuilder = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('59cb6d934c9fe22d52baf9011a7b3a39',  $configurationBuilder_reference, 'TYPO3\Flow\Property\PropertyMappingConfigurationBuilder', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Property\PropertyMappingConfigurationBuilder'); });
			}
		}
	}
}
#