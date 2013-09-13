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

/**
 * Converter which transforms from different input formats into DateTime objects.
 *
 * Source can be either a string or an array. The date string is expected to be formatted
 * according to DEFAULT_DATE_FORMAT.
 *
 * But the default date format can be overridden in the initialize*Action() method like this::
 *
 *  $this->arguments['<argumentName>']
 *    ->getPropertyMappingConfiguration()
 *    ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
 *    ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', \TYPO3\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');
 *
 * If the source is of type array, it is possible to override the format in the source::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'dateFormat' => '<dateFormat>'
 *  );
 *
 * By using an array as source you can also override time and timezone of the created DateTime object::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'hour' => '<hour>', // integer
 *   'minute' => '<minute>', // integer
 *   'seconds' => '<seconds>', // integer
 *   'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 *  );
 *
 * As an alternative to providing the date as string, you might supply day, month and year as array items each::
 *
 *  array(
 *   'day' => '<day>', // integer
 *   'month' => '<month>', // integer
 *   'year' => '<year>', // integer
 *  );
 *
 * @api
 * @Flow\Scope("singleton")
 */
class DateTimeConverter_Original extends AbstractTypeConverter {

	/**
	 * @var string
	 */
	const CONFIGURATION_DATE_FORMAT = 'dateFormat';

	/**
	 * The default date format is "YYYY-MM-DDT##:##:##+##:##", for example "2005-08-15T15:52:01+00:00"
	 * according to the W3C standard @see http://www.w3.org/TR/NOTE-datetime.html
	 *
	 * @var string
	 */
	const DEFAULT_DATE_FORMAT = \DateTime::W3C;

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'integer', 'array');

	/**
	 * @var string
	 */
	protected $targetType = 'DateTime';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * If conversion is possible.
	 *
	 * @param string $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		if (!is_callable(array($targetType, 'createFromFormat'))) {
			return FALSE;
		}
		if (is_array($source)) {
			return TRUE;
		}
		if (is_integer($source)) {
			return TRUE;
		}
		return is_string($source);
	}

	/**
	 * Converts $source to a \DateTime using the configured dateFormat
	 *
	 * @param string|integer|array $source the string to be converted to a \DateTime object
	 * @param string $targetType must be "DateTime"
	 * @param array $convertedChildProperties not used currently
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \DateTime
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$dateFormat = $this->getDefaultDateFormat($configuration);
		if (is_string($source)) {
			$dateAsString = $source;
		} elseif (is_integer($source)) {
			$dateAsString = strval($source);
		} else {
			if (isset($source['date']) && is_string($source['date'])) {
				$dateAsString = $source['date'];
			} elseif (isset($source['date']) && is_integer($source['date'])) {
				$dateAsString = strval($source['date']);
			} elseif ($this->isDatePartKeysProvided($source)) {
				if ($source['day'] < 1 || $source['month'] < 1 || $source['year'] < 1) {
					return new \TYPO3\Flow\Validation\Error('Could not convert the given date parts into a DateTime object because one or more parts were 0.', 1333032779);
				}
				$dateAsString = sprintf('%d-%d-%d', $source['year'], $source['month'], $source['day']);
			} else {
				throw new \TYPO3\Flow\Property\Exception\TypeConverterException('Could not convert the given source into a DateTime object because it was not an array with a valid date as a string', 1308003914);
			}
			if (isset($source['dateFormat']) && strlen($source['dateFormat']) > 0) {
				$dateFormat = $source['dateFormat'];
			}
		}
		if ($dateAsString === '') {
			return NULL;
		}
		if (ctype_digit($dateAsString) && $configuration === NULL && (!is_array($source) || !isset($source['dateFormat']))) {
			$dateFormat = 'U';
		}
		if (is_array($source) && isset($source['timezone']) && strlen($source['timezone']) !== 0) {
			try {
				$timezone = new \DateTimeZone($source['timezone']);
			} catch (\Exception $e) {
				throw new \TYPO3\Flow\Property\Exception\TypeConverterException('The specified timezone "' . $source['timezone'] . '" is invalid.', 1308240974);
			}
			$date = $targetType::createFromFormat($dateFormat, $dateAsString, $timezone);
		} else {
			$date = $targetType::createFromFormat($dateFormat, $dateAsString);
		}
		if ($date === FALSE) {
			return new \TYPO3\Flow\Validation\Error('The date "%s" was not recognized (for format "%s").', 1307719788, array($dateAsString, $dateFormat));
		}
		if (is_array($source)) {
			$this->overrideTimeIfSpecified($date, $source);
		}
		return $date;
	}

	/**
	 * Returns whether date information (day, month, year) are present as keys in $source.
	 * @param $source
	 * @return bool
	 */
	protected function isDatePartKeysProvided(array $source) {
		return isset($source['day']) && ctype_digit($source['day'])
			&& isset($source['month']) && ctype_digit($source['month'])
			&& isset($source['year']) && ctype_digit($source['year']);
	}

	/**
	 * Determines the default date format to use for the conversion.
	 * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
	 *
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function getDefaultDateFormat(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		}
		$dateFormat = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', self::CONFIGURATION_DATE_FORMAT);
		if ($dateFormat === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		} elseif ($dateFormat !== NULL && !is_string($dateFormat)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat)) . '" given', 1307719569);
		}
		return $dateFormat;
	}

	/**
	 * Overrides hour, minute & second of the given date with the values in the $source array
	 *
	 * @param \DateTime $date
	 * @param array $source
	 * @return void
	 */
	protected function overrideTimeIfSpecified(\DateTime $date, array $source) {
		if (!isset($source['hour']) && !isset($source['minute']) && !isset($source['second'])) {
			return;
		}
		$hour = isset($source['hour']) ? (integer)$source['hour'] : 0;
		$minute = isset($source['minute']) ? (integer)$source['minute'] : 0;
		$second = isset($source['second']) ? (integer)$source['second'] : 0;
		$date->setTime($hour, $minute, $second);
	}

}
namespace TYPO3\Flow\Property\TypeConverter;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Converter which transforms from different input formats into DateTime objects.
 * 
 * Source can be either a string or an array. The date string is expected to be formatted
 * according to DEFAULT_DATE_FORMAT.
 * 
 * But the default date format can be overridden in the initialize*Action() method like this::
 * 
 *  $this->arguments['<argumentName>']
 *    ->getPropertyMappingConfiguration()
 *    ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
 *    ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', \TYPO3\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');
 * 
 * If the source is of type array, it is possible to override the format in the source::
 * 
 *  array(
 *   'date' => '<dateString>',
 *   'dateFormat' => '<dateFormat>'
 *  );
 * 
 * By using an array as source you can also override time and timezone of the created DateTime object::
 * 
 *  array(
 *   'date' => '<dateString>',
 *   'hour' => '<hour>', // integer
 *   'minute' => '<minute>', // integer
 *   'seconds' => '<seconds>', // integer
 *   'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 *  );
 * 
 * As an alternative to providing the date as string, you might supply day, month and year as array items each::
 * 
 *  array(
 *   'day' => '<day>', // integer
 *   'month' => '<month>', // integer
 *   'year' => '<year>', // integer
 *  );
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class DateTimeConverter extends DateTimeConverter_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Property\TypeConverter\DateTimeConverter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', $this);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Property\TypeConverter\DateTimeConverter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Property\TypeConverter\DateTimeConverter');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', $propertyName, 'transient')) continue;
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