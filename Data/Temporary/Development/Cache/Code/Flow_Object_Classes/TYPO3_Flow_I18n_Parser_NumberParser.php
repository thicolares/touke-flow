<?php
namespace TYPO3\Flow\I18n\Parser;

/*
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
 * Parser for numbers.
 *
 * This parser does not support full syntax of number formats as defined in
 * CLDR. It uses parsed formats from NumbersReader class.
 *
 * @Flow\Scope("singleton")
 * @see \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader
 * @api
 * @todo Currency support
 */
class NumberParser_Original {

	/**
	 * Regex pattern for matching one or more digits.
	 */
	const PATTERN_MATCH_DIGITS = '/^[0-9]+$/';

	/**
	 * Regex pattern for matching all except digits. It's used for clearing
	 * string in lenient mode.
	 */
	const PATTERN_MATCH_NOT_DIGITS = '/[^0-9]+/';

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader
	 */
	protected $numbersReader;

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader $numbersReader
	 * @return void
	 */
	public function injectNumbersReader(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader $numbersReader) {
		$this->numbersReader = $numbersReader;
	}

	/**
	 * Parses number given as a string using provided format.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param string $format Number format to use
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Parsed float number or FALSE on failure
	 * @api
	 */
	public function parseNumberWithCustomPattern($numberToParse, $format, \TYPO3\Flow\I18n\Locale $locale, $strictMode = TRUE) {
		return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseCustomFormat($format), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
	}

	/**
	 * Parses decimal number using proper format from CLDR.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Parsed float number or FALSE on failure
	 * @api
	 */
	public function parseDecimalNumber($numberToParse, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, $strictMode = TRUE) {
		\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::validateFormatLength($formatLength);
		return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
	}

	/**
	 * Parses percent number using proper format from CLDR.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Parsed float number or FALSE on failure
	 * @api
	 */
	public function parsePercentNumber($numberToParse, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, $strictMode = TRUE) {
		\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::validateFormatLength($formatLength);
		return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
	}

	/**
	 * Parses number using parsed format, in strict or lenient mode.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param array $parsedFormat Parsed format (from NumbersReader)
	 * @param array $localizedSymbols An array with symbols to use
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Parsed float number or FALSE on failure
	 */
	protected function doParsingWithParsedFormat($numberToParse, array $parsedFormat, array $localizedSymbols, $strictMode) {
		return ($strictMode) ? $this->doParsingInStrictMode($numberToParse, $parsedFormat, $localizedSymbols) : $this->doParsingInLenientMode($numberToParse, $parsedFormat, $localizedSymbols);
	}

	/**
	 * Parses number in strict mode.
	 *
	 * In strict mode parser checks all constraints of provided parsed format,
	 * and if any of them is not fullfiled, parsing fails (FALSE is returned).
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param array $parsedFormat Parsed format (from NumbersReader)
	 * @param array $localizedSymbols An array with symbols to use
	 * @return mixed Parsed float number or FALSE on failure
	 */
	protected function doParsingInStrictMode($numberToParse, array $parsedFormat, array $localizedSymbols) {
		$numberIsNegative = FALSE;

		if (!empty($parsedFormat['negativePrefix']) && !empty($parsedFormat['negativeSuffix'])) {
			if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['negativePrefix']) && \TYPO3\Flow\I18n\Utility::stringEndsWith($numberToParse, $parsedFormat['negativeSuffix'])) {
				$numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']), - strlen($parsedFormat['negativeSuffix']));
				$numberIsNegative = TRUE;
			}
		} elseif (!empty($parsedFormat['negativePrefix']) && \TYPO3\Flow\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['negativePrefix'])) {
			$numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']));
			$numberIsNegative = TRUE;
		} elseif (!empty($parsedFormat['negativeSuffix']) && \TYPO3\Flow\I18n\Utility::stringEndsWith($numberToParse, $parsedFormat['negativeSuffix'])) {
			$numberToParse = substr($numberToParse, 0, - strlen($parsedFormat['negativeSuffix']));
			$numberIsNegative = TRUE;
		}

		if (!$numberIsNegative) {
			if (!empty($parsedFormat['positivePrefix']) && !empty($parsedFormat['positiveSuffix'])) {
				if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['positivePrefix']) && \TYPO3\Flow\I18n\Utility::stringEndsWith($numberToParse, $parsedFormat['positiveSuffix'])) {
					$numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']), - strlen($parsedFormat['positiveSuffix']));
				} else {
					return FALSE;
				}
			} elseif (!empty($parsedFormat['positivePrefix'])) {
				if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['positivePrefix'])) {
					$numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']));
				} else {
					return FALSE;
				}
			} elseif (!empty($parsedFormat['positiveSuffix'])) {
				if (\TYPO3\Flow\I18n\Utility::stringEndsWith($numberToParse, $parsedFormat['positiveSuffix'])) {
					$numberToParse = substr($numberToParse, 0, - strlen($parsedFormat['positiveSuffix']));
				} else {
					return FALSE;
				}
			}
		}

		$positionOfDecimalSeparator = strpos($numberToParse, $localizedSymbols['decimal']);
		if ($positionOfDecimalSeparator === FALSE) {
			$numberToParse = str_replace($localizedSymbols['group'], '', $numberToParse);

			if (strlen($numberToParse) < $parsedFormat['minIntegerDigits']) {
				return FALSE;
			} elseif (preg_match(self::PATTERN_MATCH_DIGITS, $numberToParse, $matches) !== 1) {
				return FALSE;
			}

			$integerPart = $numberToParse;
			$decimalPart = FALSE;
		} else {
			if ($positionOfDecimalSeparator === 0 && $positionOfDecimalSeparator === strlen($numberToParse) - 1) {
				return FALSE;
			}

			$numberToParse = str_replace(array($localizedSymbols['group'], $localizedSymbols['decimal']), array('', '.'), $numberToParse);

			$positionOfDecimalSeparator = strpos($numberToParse, '.');
			$integerPart = substr($numberToParse, 0, $positionOfDecimalSeparator);
			$decimalPart = substr($numberToParse, $positionOfDecimalSeparator + 1);
		}

		if (strlen($integerPart) < $parsedFormat['minIntegerDigits']) {
			return FALSE;
		} elseif (preg_match(self::PATTERN_MATCH_DIGITS, $integerPart, $matches) !== 1) {
			return FALSE;
		}

		$parsedNumber = (int)$integerPart;

		if ($decimalPart !== FALSE) {
			$countOfDecimalDigits = strlen($decimalPart);
			if ($countOfDecimalDigits < $parsedFormat['minDecimalDigits'] || $countOfDecimalDigits > $parsedFormat['maxDecimalDigits']) {
				return FALSE;
			} elseif (preg_match(self::PATTERN_MATCH_DIGITS, $decimalPart, $matches) !== 1) {
				return FALSE;
			}

			$parsedNumber = (float)($integerPart . '.' . $decimalPart);
		}

		$parsedNumber /= $parsedFormat['multiplier'];

		if ($parsedFormat['rounding'] !== 0.0 && ($parsedNumber - (int)($parsedNumber / $parsedFormat['rounding']) * $parsedFormat['rounding']) !== 0.0) {
			return FALSE;
		}

		if ($numberIsNegative) {
			$parsedNumber = 0 - $parsedNumber;
		}

		return $parsedNumber;
	}

	/**
	 * Parses number in lenient mode.
	 *
	 * Lenient parsing ignores everything that can be ignored, and tries to
	 * extract number from the string, even if it's not well formed.
	 *
	 * Implementation is simple but should work more often than strict parsing.
	 *
	 * Algorithm:
	 * 1. Find first digit
	 * 2. Find last digit
	 * 3. Find decimal separator between first and last digit (if any)
	 * 4. Remove non-digits from integer part
	 * 5. Remove non-digits from decimal part (optional)
	 * 6. Try to match negative prefix before first digit
	 * 7. Try to match negative suffix after last digit
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param array $parsedFormat Parsed format (from NumbersReader)
	 * @param array $localizedSymbols An array with symbols to use
	 * @return mixed Parsed float number or FALSE on failure
	 */
	protected function doParsingInLenientMode($numberToParse, array $parsedFormat, array $localizedSymbols) {
		$numberIsNegative = FALSE;
		$positionOfFirstDigit = NULL;
		$positionOfLastDigit = NULL;

		$charactersOfNumberString = str_split($numberToParse);
		foreach ($charactersOfNumberString as $position => $character) {
			if (ord($character) >= 48 && ord($character) <= 57) {
				$positionOfFirstDigit = $position;
				break;
			}
		}

		if ($positionOfFirstDigit === NULL) {
			return FALSE;
		}

		krsort($charactersOfNumberString);
		foreach ($charactersOfNumberString as $position => $character) {
			if (ord($character) >= 48 && ord($character) <= 57) {
				$positionOfLastDigit = $position;
				break;
			}
		}

		$positionOfDecimalSeparator = strrpos($numberToParse, $localizedSymbols['decimal'], $positionOfFirstDigit);
		if ($positionOfDecimalSeparator === FALSE) {
			$integerPart = substr($numberToParse, $positionOfFirstDigit, $positionOfLastDigit - $positionOfFirstDigit + 1);
			$decimalPart = FALSE;
		} else {
			$integerPart = substr($numberToParse, $positionOfFirstDigit, $positionOfDecimalSeparator - $positionOfFirstDigit);
			$decimalPart = substr($numberToParse, $positionOfDecimalSeparator + 1, $positionOfLastDigit - $positionOfDecimalSeparator);
		}

		$parsedNumber = (int)preg_replace(self::PATTERN_MATCH_NOT_DIGITS, '', $integerPart);

		if ($decimalPart !== FALSE) {
			$decimalPart = (int)preg_replace(self::PATTERN_MATCH_NOT_DIGITS, '', $decimalPart);
			$parsedNumber = (float)($parsedNumber . '.' . $decimalPart);
		}

		$partBeforeNumber = substr($numberToParse, 0, $positionOfFirstDigit);
		$partAfterNumber = substr($numberToParse, - (strlen($numberToParse) - $positionOfLastDigit - 1));

		if (!empty($parsedFormat['negativePrefix']) && !empty($parsedFormat['negativeSuffix'])) {
			if (\TYPO3\Flow\I18n\Utility::stringEndsWith($partBeforeNumber, $parsedFormat['negativePrefix']) && \TYPO3\Flow\I18n\Utility::stringBeginsWith($partAfterNumber, $parsedFormat['negativeSuffix'])) {
				$numberIsNegative = TRUE;
			}
		} elseif (!empty($parsedFormat['negativePrefix']) && \TYPO3\Flow\I18n\Utility::stringEndsWith($partBeforeNumber, $parsedFormat['negativePrefix'])) {
			$numberIsNegative = TRUE;
		} elseif (!empty($parsedFormat['negativeSuffix']) && \TYPO3\Flow\I18n\Utility::stringBeginsWith($partAfterNumber, $parsedFormat['negativeSuffix'])) {
			$numberIsNegative = TRUE;
		}

		$parsedNumber /= $parsedFormat['multiplier'];

		if ($numberIsNegative) {
			$parsedNumber = 0 - $parsedNumber;
		}

		return $parsedNumber;
	}
}

namespace TYPO3\Flow\I18n\Parser;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Parser for numbers.
 * 
 * This parser does not support full syntax of number formats as defined in
 * CLDR. It uses parsed formats from NumbersReader class.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class NumberParser extends NumberParser_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Parser\NumberParser') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Parser\NumberParser', $this);
		if ('TYPO3\Flow\I18n\Parser\NumberParser' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Parser\NumberParser') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Parser\NumberParser', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Parser\NumberParser');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Parser\NumberParser', $propertyName, 'transient')) continue;
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
		$this->injectNumbersReader(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Cldr\Reader\NumbersReader'));
	}
}
#