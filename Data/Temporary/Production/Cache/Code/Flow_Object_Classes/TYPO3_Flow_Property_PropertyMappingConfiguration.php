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


/**
 * Concrete configuration object for the PropertyMapper.
 *
 * @api
 */
class PropertyMappingConfiguration_Original implements \TYPO3\Flow\Property\PropertyMappingConfigurationInterface {

	/**
	 * Placeholder in property paths for multi-valued types
	 */
	const PROPERTY_PATH_PLACEHOLDER = '*';

	/**
	 * multi-dimensional array which stores type-converter specific configuration:
	 * 1. Dimension: Fully qualified class name of the type converter
	 * 2. Dimension: Configuration Key
	 * Value: Configuration Value
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Stores the configuration for specific child properties.
	 *
	 * @var array<\TYPO3\Flow\Property\PropertyMappingConfigurationInterface>
	 */
	protected $subConfigurationForProperty = array();

	/**
	 * Keys which should be renamed
	 *
	 * @var array
	 */
	protected $mapping = array();

	/**
	 * @var \TYPO3\Flow\Property\TypeConverterInterface
	 */
	protected $typeConverter = NULL;

	/**
	 * List of allowed property names to be converted
	 *
	 * @var array
	 */
	protected $propertiesToBeMapped = array();

	/**
	 * List of disallowed property names which will be ignored while property mapping
	 *
	 * @var array
	 */
	protected $propertiesNotToBeMapped = array();

	/**
	 * If TRUE, unknown properties will be mapped.
	 *
	 * @var boolean
	 */
	protected $mapUnknownProperties = FALSE;

	/**
	 * The behavior is as follows:
	 *
	 * - if a property has been explicitly forbidden using allowAllPropertiesExcept(...), it is directly rejected
	 * - if a property has been allowed using allowProperties(...), it is directly allowed.
	 * - if allowAllProperties* has been called, we allow unknown properties
	 * - else, return FALSE.
	 *
	 * @param string $propertyName
	 * @return boolean TRUE if the given propertyName should be mapped, FALSE otherwise.
	 */
	public function shouldMap($propertyName) {
		if (isset($this->propertiesNotToBeMapped[$propertyName])) {
			return FALSE;
		}

		if (isset($this->propertiesToBeMapped[$propertyName])) {
			return TRUE;
		}

		if (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
			return TRUE;
		}

		return $this->mapUnknownProperties;
	}

	/**
	 * Allow all properties in property mapping, even unknown ones.
	 *
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function allowAllProperties() {
		$this->mapUnknownProperties = TRUE;
		return $this;
	}

	/**
	 * Allow a list of specific properties. All arguments of
	 * allowProperties are used here (varargs).
	 *
	 * Example: allowProperties('title', 'content', 'author')
	 *
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function allowProperties() {
		foreach (func_get_args() as $propertyName) {
			$this->propertiesToBeMapped[$propertyName] = $propertyName;
		}
		return $this;
	}

	/**
	 * Allow all properties during property mapping, but reject a few
	 * selected ones (blacklist).
	 *
	 * Example: allowAllPropertiesExcept('password', 'userGroup')
	 *
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function allowAllPropertiesExcept() {
		$this->mapUnknownProperties = TRUE;

		foreach (func_get_args() as $propertyName) {
			$this->propertiesNotToBeMapped[$propertyName] = $propertyName;
		}
		return $this;
	}

	/**
	 * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
	 *
	 * @param string $propertyName
	 * @return \TYPO3\Flow\Property\PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
	 * @api
	 */
	public function getConfigurationFor($propertyName) {
		if (isset($this->subConfigurationForProperty[$propertyName])) {
			return $this->subConfigurationForProperty[$propertyName];
		} elseif (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
			return $this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER];
		}

		return new \TYPO3\Flow\Property\PropertyMappingConfiguration();
	}

	/**
	 * Maps the given $sourcePropertyName to a target property name.
	 *
	 * @param string $sourcePropertyName
	 * @return string property name of target
	 * @api
	 */
	public function getTargetPropertyName($sourcePropertyName) {
		if (isset($this->mapping[$sourcePropertyName])) {
			return $this->mapping[$sourcePropertyName];
		}
		return $sourcePropertyName;
	}

	/**
	 * @param string $typeConverterClassName
	 * @param string $key
	 * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration.
	 * @api
	 */
	public function getConfigurationValue($typeConverterClassName, $key) {
		if (!isset($this->configuration[$typeConverterClassName][$key])) {
			return NULL;
		}

		return $this->configuration[$typeConverterClassName][$key];
	}

	/**
	 * Define renaming from Source to Target property.
	 *
	 * @param string $sourcePropertyName
	 * @param string $targetPropertyName
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function setMapping($sourcePropertyName, $targetPropertyName) {
		$this->mapping[$sourcePropertyName] = $targetPropertyName;
		return $this;
	}

	/**
	 * Set all options for the given $typeConverter.
	 *
	 * @param string $typeConverter class name of type converter
	 * @param array $options
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function setTypeConverterOptions($typeConverter, array $options) {
		foreach ($this->getTypeConvertersWithParentClasses($typeConverter) as $typeConverter) {
			$this->configuration[$typeConverter] = $options;
		}
		return $this;
	}

	/**
	 * Set a single option (denoted by $optionKey) for the given $typeConverter.
	 *
	 * @param string $typeConverter class name of type converter
	 * @param string $optionKey
	 * @param mixed $optionValue
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function setTypeConverterOption($typeConverter, $optionKey, $optionValue) {
		foreach ($this->getTypeConvertersWithParentClasses($typeConverter) as $typeConverter) {
			$this->configuration[$typeConverter][$optionKey] = $optionValue;
		}
		return $this;
	}

	/**
	 * Get type converter classes including parents for the given type converter
	 *
	 * When setting an option on a subclassed type converter, this option must also be set on
	 * all its parent type converters.
	 *
	 * @param string $typeConverter The type converter class
	 * @return array Class names of type converters
	 */
	protected function getTypeConvertersWithParentClasses($typeConverter) {
		$typeConverterClasses = class_parents($typeConverter);
		$typeConverterClasses = $typeConverterClasses === FALSE ? array() : $typeConverterClasses;
		$typeConverterClasses[] = $typeConverter;
		return $typeConverterClasses;
	}

	/**
	 * Returns the configuration for the specific property path, ready to be modified. Should be used
	 * inside a fluent interface like:
	 * $configuration->forProperty('foo.bar')->setTypeConverterOption(....)
	 *
	 * @param string $propertyPath
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration (or a subclass thereof)
	 * @api
	 */
	public function forProperty($propertyPath) {
		$splittedPropertyPath = explode('.', $propertyPath);
		return $this->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Traverse the property configuration. Only used by forProperty().
	 *
	 * @param array $splittedPropertyPath
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration (or a subclass thereof)
	 */
	public function traverseProperties(array $splittedPropertyPath) {
		if (count($splittedPropertyPath) === 0) {
			return $this;
		}

		$currentProperty = array_shift($splittedPropertyPath);
		if (!isset($this->subConfigurationForProperty[$currentProperty])) {
			$type = get_class($this);
			if (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
				$this->subConfigurationForProperty[$currentProperty] = clone $this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER];
			} else {
				$this->subConfigurationForProperty[$currentProperty] = new $type;
			}
		}
		return $this->subConfigurationForProperty[$currentProperty]->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Return the type converter set for this configuration.
	 *
	 * @return \TYPO3\Flow\Property\TypeConverterInterface
	 * @api
	 */
	public function getTypeConverter() {
		return $this->typeConverter;
	}

	/**
	 * Set a type converter which should be used for this specific conversion.
	 *
	 * @param \TYPO3\Flow\Property\TypeConverterInterface $typeConverter
	 * @return \TYPO3\Flow\Property\PropertyMappingConfiguration this
	 * @api
	 */
	public function setTypeConverter(\TYPO3\Flow\Property\TypeConverterInterface $typeConverter) {
		$this->typeConverter = $typeConverter;
		return $this;
	}
}
namespace TYPO3\Flow\Property;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Concrete configuration object for the PropertyMapper.
 */
class PropertyMappingConfiguration extends PropertyMappingConfiguration_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Property\PropertyMappingConfiguration');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Property\PropertyMappingConfiguration', $propertyName, 'transient')) continue;
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