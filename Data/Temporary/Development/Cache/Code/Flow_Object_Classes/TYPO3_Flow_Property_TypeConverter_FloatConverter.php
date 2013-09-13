<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\Flow\I18n\Cldr\Reader\NumbersReader;

/**
 * Converter which transforms a simple type to a float.
 *
 * This is basically done by simply casting it, except you provide some configuration options
 * which will make this converter use Flow's locale parsing capabilities in order to respect
 * deviating decimal separators.
 *
 * **Advanced usage in action controller context**
 *
 * *Using default locale*::
 *
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', TRUE
 *  	);
 *  }
 *
 * Just providing TRUE as option value will use the current default locale. In case that default locale is "DE"
 * for Germany for example, where a comma is used as decimal separator, the mentioned code will return
 * (float)15.5 when the input was (string)"15,50".
 *
 * *Using arbitrary locale*::
 *
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', 'fr'
 *  	);
 *  }
 *
 * **Parsing mode**
 *
 * There are two parsing modes available, strict and lenient mode. Strict mode will check all constraints of the provided
 * format, and if any of them are not fulfilled, the conversion will not take place.
 * In Lenient mode the parser will try to extract the intended number from the string, even if it's not well formed.
 * Default for strict mode is TRUE.
 *
 * *Example setting lenient mode (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'strictMode', FALSE
 *  );
 *
 * **Format type**
 *
 * Format type can be decimal, percent or currency; represented as class constant FORMAT_TYPE_DECIMAL,
 * FORMAT_TYPE_PERCENT or FORMAT_TYPE_CURRENCY of class TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
 * Default, if none given, is FORMAT_TYPE_DECIMAL.
 *
 * *Example setting format type `currency` (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatType', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
 *  );
 *
 * **Format length**
 *
 * Format type can be default, full, long, medium or short; represented as class constant FORMAT_LENGTH_DEFAULT,
 * FORMAT_LENGTH_FULL, FORMAT_LENGTH_LONG etc., of class  TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
 * The format length has a technical background in the CLDR repository, and specifies whether a different number
 * pattern should be used. In most cases leaving this DEFAULT would be the correct choice.
 *
 * *Example setting format length (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatLength', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_FULL
 *  );
 *
 * @api
 * @Flow\Scope("singleton")
 */
class FloatConverter_Original extends AbstractTypeConverter {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Parser\NumberParser
	 */
	protected $numberParser;

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('float', 'integer', 'string');

	/**
	 * @var string
	 */
	protected $targetType = 'float';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, by doing a typecast.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return float|\TYPO3\Flow\Error\Error
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source === NULL || strlen($source) === 0) {
			return NULL;
		} elseif (is_string($source) && $configuration instanceof \TYPO3\Flow\Property\PropertyMappingConfigurationInterface) {
			$source = $this->parseUsingLocaleIfConfigured($source, $configuration);
			if ($source instanceof Error) {
				return $source;
			}
		}

		if (!is_numeric($source)) {
			return new Error('"%s" cannot be converted to a float value.' , 1332934124, array($source));
		}
		return (float)$source;
	}

	/**
	 * Tries to parse the input using the NumberParser.
	 *
	 * @param string $source
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return float|\TYPO3\Flow\Validation\Error Parsed float number or error
	 */
	protected function parseUsingLocaleIfConfigured($source, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuration = $this->getConfigurationKeysAndValues($configuration, array('locale', 'strictMode', 'formatLength', 'formatType'));

		if ($configuration['locale'] === NULL) {
			return $source;
		} elseif ($configuration['locale'] === TRUE) {
			$locale = $this->localizationService->getConfiguration()->getCurrentLocale();
		} elseif (is_string($configuration['locale'])) {
			$locale = new \TYPO3\Flow\I18n\Locale($configuration['locale']);
		} elseif ($configuration['locale'] instanceof \TYPO3\Flow\I18n\Locale) {
			$locale = $configuration['locale'];
		}

		if (!($locale instanceof \TYPO3\Flow\I18n\Locale)) {
			$exceptionMessage = 'Determined locale is not of type "\TYPO3\Flow\I18n\Locale", but of type "' . (is_object($locale) ? get_class($locale) : gettype($locale)) . '".';
			throw new InvalidPropertyMappingConfigurationException($exceptionMessage, 1334837413);
		}

		if ($configuration['strictMode'] === NULL || $configuration['strictMode'] === TRUE) {
			$strictMode = TRUE;
		} else {
			$strictMode = FALSE;
		}

		if ($configuration['formatLength'] !== NULL) {
			$formatLength = $configuration['formatLength'];
			NumbersReader::validateFormatLength($formatLength);
		} else {
			$formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT;
		}

		if ($configuration['formatType'] !== NULL) {
			$formatType = $configuration['formatType'];
			\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::validateFormatType($formatType);
		} else {
			$formatType = NumbersReader::FORMAT_TYPE_DECIMAL;
		}

		if ($formatType === NumbersReader::FORMAT_TYPE_PERCENT) {
			$return = $this->numberParser->parsePercentNumber($source, $locale, $formatLength, $strictMode);
			if ($return === FALSE) {
				$return = new Error('A valid percent number is expected.', 1334839253);
			}
		} else {
			$return = $this->numberParser->parseDecimalNumber($source, $locale, $formatLength, $strictMode);
			if ($return === FALSE) {
				$return = new Error('A valid decimal number is expected.', 1334839260);
			}
		}

		return $return;
	}

	/**
	 * Helper method to collect configuration for this class.
	 *
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @param array $configurationKeys
	 * @return array
	 */
	protected function getConfigurationKeysAndValues(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration, array $configurationKeys) {
		$keysAndValues = array();
		foreach ($configurationKeys as $configurationKey) {
			$keysAndValues[$configurationKey] = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\FloatConverter', $configurationKey);
		}
		return $keysAndValues;
	}
}
namespace TYPO3\Flow\Property\TypeConverter;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Converter which transforms a simple type to a float.
 * 
 * This is basically done by simply casting it, except you provide some configuration options
 * which will make this converter use Flow's locale parsing capabilities in order to respect
 * deviating decimal separators.
 * 
 * **Advanced usage in action controller context**
 * 
 * *Using default locale*::
 * 
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', TRUE
 *  	);
 *  }
 * 
 * Just providing TRUE as option value will use the current default locale. In case that default locale is "DE"
 * for Germany for example, where a comma is used as decimal separator, the mentioned code will return
 * (float)15.5 when the input was (string)"15,50".
 * 
 * *Using arbitrary locale*::
 * 
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', 'fr'
 *  	);
 *  }
 * 
 * **Parsing mode**
 * 
 * There are two parsing modes available, strict and lenient mode. Strict mode will check all constraints of the provided
 * format, and if any of them are not fulfilled, the conversion will not take place.
 * In Lenient mode the parser will try to extract the intended number from the string, even if it's not well formed.
 * Default for strict mode is TRUE.
 * 
 * *Example setting lenient mode (abridged)*::
 * 
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'strictMode', FALSE
 *  );
 * 
 * **Format type**
 * 
 * Format type can be decimal, percent or currency; represented as class constant FORMAT_TYPE_DECIMAL,
 * FORMAT_TYPE_PERCENT or FORMAT_TYPE_CURRENCY of class TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
 * Default, if none given, is FORMAT_TYPE_DECIMAL.
 * 
 * *Example setting format type `currency` (abridged)*::
 * 
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatType', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
 *  );
 * 
 * **Format length**
 * 
 * Format type can be default, full, long, medium or short; represented as class constant FORMAT_LENGTH_DEFAULT,
 * FORMAT_LENGTH_FULL, FORMAT_LENGTH_LONG etc., of class  TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
 * The format length has a technical background in the CLDR repository, and specifies whether a different number
 * pattern should be used. In most cases leaving this DEFAULT would be the correct choice.
 * 
 * *Example setting format length (abridged)*::
 * 
 *  ->setTypeConverterOption(
 *  	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatLength', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_FULL
 *  );
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class FloatConverter extends FloatConverter_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Property\TypeConverter\FloatConverter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\TypeConverter\FloatConverter', $this);
		if ('TYPO3\Flow\Property\TypeConverter\FloatConverter' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Property\TypeConverter\FloatConverter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\TypeConverter\FloatConverter', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Property\TypeConverter\FloatConverter');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Property\TypeConverter\FloatConverter', $propertyName, 'transient')) continue;
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
		$localizationService_reference = &$this->localizationService;
		$this->localizationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\I18n\Service');
		if ($this->localizationService === NULL) {
			$this->localizationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('d147918505b040be63714e111bab34f3', $localizationService_reference);
			if ($this->localizationService === NULL) {
				$this->localizationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('d147918505b040be63714e111bab34f3',  $localizationService_reference, 'TYPO3\Flow\I18n\Service', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Service'); });
			}
		}
		$numberParser_reference = &$this->numberParser;
		$this->numberParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\I18n\Parser\NumberParser');
		if ($this->numberParser === NULL) {
			$this->numberParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('d1a4e959f0152997e1633fba51b628c3', $numberParser_reference);
			if ($this->numberParser === NULL) {
				$this->numberParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('d1a4e959f0152997e1633fba51b628c3',  $numberParser_reference, 'TYPO3\Flow\I18n\Parser\NumberParser', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Parser\NumberParser'); });
			}
		}
	}
}
#