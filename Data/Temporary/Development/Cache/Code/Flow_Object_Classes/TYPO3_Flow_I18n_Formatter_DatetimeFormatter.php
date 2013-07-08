<?php
namespace TYPO3\Flow\I18n\Formatter;

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
 * Formatter for date and time.
 *
 * This is not full implementation of features from CLDR. These are missing:
 * - support for other calendars than Gregorian
 * - rules for displaying timezone names are simplified
 * - some less frequently used format characters are not supported
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DatetimeFormatter_Original implements \TYPO3\Flow\I18n\Formatter\FormatterInterface {

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	protected $datesReader;

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\Reader\DatesReader $datesReader
	 * @return void
	 */
	public function injectDatesReader(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader $datesReader) {
		$this->datesReader = $datesReader;
	}

	/**
	 * Formats provided value using optional style properties
	 *
	 * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param array $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
	 * @return string String representation of $value provided, or (string)$value
	 * @api
	 */
	public function format($value, \TYPO3\Flow\I18n\Locale $locale, array $styleProperties = array()) {
		if (isset($styleProperties[0])) {
			$formatType = $styleProperties[0];
			\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatType($formatType);
		} else {
			$formatType = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME;
		}

		if (isset($styleProperties[1])) {
			$formatLength = $styleProperties[1];
			\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		} else {
			$formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT;
		}

		switch ($formatType) {
			case \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE:
				return $this->formatDate($value, $locale, $formatLength);
			case \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME:
				return $this->formatTime($value, $locale, $formatLength);
			default:
				return $this->formatDateTime($value, $locale, $formatLength);
		}
	}

	/**
	 * Returns dateTime formatted by custom format, string provided in parameter.
	 *
	 * Format must obey syntax defined in CLDR specification, excluding
	 * unimplemented features (see documentation for DatesReader class).
	 *
	 * Format is remembered in this classes cache and won't be parsed again for
	 * some time.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param string $format Format string
	 * @param \TYPO3\Flow\I18n\Locale $locale A locale used for finding literals array
	 * @return string Formatted date / time. Unimplemented subformats in format string will be silently ignored
	 * @api
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	public function formatDateTimeWithCustomPattern(\DateTime $dateTime, $format, \TYPO3\Flow\I18n\Locale $locale) {
		return $this->doFormattingWithParsedFormat($dateTime, $this->datesReader->parseCustomFormat($format), $this->datesReader->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats date with format string for date defined in CLDR for particular
	 * locale.
	 *
	 * @param \DateTime $date PHP object representing particular point in time
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
	 * @return string Formatted date
	 * @api
	 */
	public function formatDate(\DateTime $date, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doFormattingWithParsedFormat($date, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats time with format string for time defined in CLDR for particular
	 * locale.
	 *
	 * @param \DateTime $time PHP object representing particular point in time
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
	 * @return string Formatted time
	 * @api
	 */
	public function formatTime(\DateTime $time, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doFormattingWithParsedFormat($time, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats dateTime with format string for date and time defined in CLDR for
	 * particular locale.
	 *
	 * First date and time are formatted separately, and then dateTime format
	 * from CLDR is used to place date and time in correct order.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
	 * @return string Formatted date and time
	 * @api
	 */
	public function formatDateTime(\DateTime $dateTime, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doFormattingWithParsedFormat($dateTime, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats provided dateTime object.
	 *
	 * Format rules defined in $parsedFormat array are used. Localizable literals
	 * are replaced with elements from $localizedLiterals array.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param array $parsedFormat An array describing format (as in $parsedFormats property)
	 * @param array $localizedLiterals An array with literals to use (as in $localizedLiterals property)
	 * @return string Formatted date / time
	 */
	protected function doFormattingWithParsedFormat(\DateTime $dateTime, array $parsedFormat, array $localizedLiterals) {
		$formattedDateTime = '';

		foreach ($parsedFormat as $subformat) {
			if (is_array($subformat)) {
					// This is just a simple string we use literally
				$formattedDateTime .= $subformat[0];
			} else {
				$formattedDateTime .= $this->doFormattingForSubpattern($dateTime, $subformat, $localizedLiterals);
			}
		}

		return $formattedDateTime;
	}

	/**
	 * Formats date or time element according to the subpattern provided.
	 *
	 * Returns a string with formatted one "part" of DateTime object (seconds,
	 * day, month etc).
	 *
	 * Not all pattern symbols defined in CLDR are supported; some of the rules
	 * are simplified. Please see the documentation for DatesReader for details.
	 *
	 * Cases in the code are ordered in such way that probably mostly used are
	 * on the top (but they are also grouped by similarity).
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param string $subformat One element of format string (e.g., 'yyyy', 'mm', etc)
	 * @param array $localizedLiterals Array of date / time literals from CLDR
	 * @return string Formatted part of date / time
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidArgumentException When $subformat use symbol that is not recognized
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	protected function doFormattingForSubpattern(\DateTime $dateTime, $subformat, array $localizedLiterals) {
		$formatLengthOfSubformat = strlen($subformat);

		switch ($subformat[0]) {
			case 'h':
				return $this->padString($dateTime->format('g'), $formatLengthOfSubformat);
			case 'H':
				return $this->padString($dateTime->format('G'), $formatLengthOfSubformat);
			case 'K':
				$hour = (int)($dateTime->format('g'));
				if ($hour === 12) $hour = 0;
				return $this->padString($hour, $formatLengthOfSubformat);
			case 'k':
				$hour = (int)($dateTime->format('G'));
				if ($hour === 0) $hour = 24;
				return $this->padString($hour, $formatLengthOfSubformat);
			case 'a':
				return $localizedLiterals['dayPeriods']['format']['wide'][$dateTime->format('a')];
			case 'm':
				return $this->padString((int)($dateTime->format('i')), $formatLengthOfSubformat);
			case 's':
				return $this->padString((int)($dateTime->format('s')), $formatLengthOfSubformat);
			case 'S':
				return (string)round($dateTime->format('u'), $formatLengthOfSubformat);
			case 'd':
				return $this->padString($dateTime->format('j'), $formatLengthOfSubformat);
			case 'D':
				return $this->padString((int)($dateTime->format('z') + 1), $formatLengthOfSubformat);
			case 'F':
				return (int)(($dateTime->format('j') + 6) / 7);
			case 'M':
			case 'L':
				$month = (int)$dateTime->format('n');
				$formatType = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
				if ($formatLengthOfSubformat <= 2) {
					return $this->padString($month, $formatLengthOfSubformat);
				} else if ($formatLengthOfSubformat === 3) {
					return $localizedLiterals['months'][$formatType]['abbreviated'][$month];
				} else if ($formatLengthOfSubformat === 4) {
					return $localizedLiterals['months'][$formatType]['wide'][$month];
				} else {
					return $localizedLiterals['months'][$formatType]['narrow'][$month];
				}
			case 'y':
				$year = (int)$dateTime->format('Y');
				if ($formatLengthOfSubformat === 2) $year %= 100;
				return $this->padString($year, $formatLengthOfSubformat);
			case 'E':
				$day = strtolower($dateTime->format('D'));
				if ($formatLengthOfSubformat <= 3) {
					return $localizedLiterals['days']['format']['abbreviated'][$day];
				} else if ($formatLengthOfSubformat === 4) {
					return $localizedLiterals['days']['format']['wide'][$day];
				} else {
					return $localizedLiterals['days']['format']['narrow'][$day];
				}
			case 'w':
				return $this->padString($dateTime->format('W'), $formatLengthOfSubformat);
			case 'W':
				return (string)((((int)$dateTime->format('W') - 1) % 4) + 1);
			case 'Q':
			case 'q':
				$quarter = (int)($dateTime->format('n') / 3.1) + 1;
				$formatType = ($subformat[0] === 'q') ? 'stand-alone' : 'format';
				if ($formatLengthOfSubformat <= 2) {
					return $this->padString($quarter, $formatLengthOfSubformat);
				} else if ($formatLengthOfSubformat === 3) {
					return $localizedLiterals['quarters'][$formatType]['abbreviated'][$quarter];
				} else {
					return $localizedLiterals['quarters'][$formatType]['wide'][$quarter];
				}
			case 'G':
				$era = (int)($dateTime->format('Y') > 0);
				if ($formatLengthOfSubformat <= 3) {
					return $localizedLiterals['eras']['eraAbbr'][$era];
				} else if ($formatLengthOfSubformat === 4) {
					return $localizedLiterals['eras']['eraNames'][$era];
				} else {
					return $localizedLiterals['eras']['eraNarrow'][$era];
				}
			case 'v':
			case 'z':
				if ($formatLengthOfSubformat <= 3) {
					return $dateTime->format('T');
				} else {
					return $dateTime->format('e');
				}
			case 'Y':
			case 'u':
			case 'l':
			case 'g':
			case 'e':
			case 'c':
			case 'A':
			case 'Z':
			case 'V':
					// Silently ignore unsupported formats
				return '';
			default:
				throw new \TYPO3\Flow\I18n\Exception\InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time formatting.', 1276106678);
		}
	}

	/**
	 * Pads given string to the specified length with zeros.
	 *
	 * @param string $string
	 * @param int $formatLength
	 * @return string Padded string (can be unchanged if $formatLength is lower than length of string)
	 */
	protected function padString($string, $formatLength) {
		return str_pad($string, $formatLength, '0', \STR_PAD_LEFT);
	}
}

namespace TYPO3\Flow\I18n\Formatter;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Formatter for date and time.
 * 
 * This is not full implementation of features from CLDR. These are missing:
 * - support for other calendars than Gregorian
 * - rules for displaying timezone names are simplified
 * - some less frequently used format characters are not supported
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class DatetimeFormatter extends DatetimeFormatter_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Formatter\DatetimeFormatter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Formatter\DatetimeFormatter', $this);
		if ('TYPO3\Flow\I18n\Formatter\DatetimeFormatter' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Formatter\DatetimeFormatter') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Formatter\DatetimeFormatter', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Formatter\DatetimeFormatter');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Formatter\DatetimeFormatter', $propertyName, 'transient')) continue;
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
		$this->injectDatesReader(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Cldr\Reader\DatesReader'));
	}
}
#