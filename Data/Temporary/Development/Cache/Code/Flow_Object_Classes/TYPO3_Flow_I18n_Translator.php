<?php
namespace TYPO3\Flow\I18n;

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
 * A class for translating messages
 *
 * Messages (labels) can be translated in two modes:
 * - by original label: untranslated label is used as a key
 * - by ID: string identifier is used as a key (eg. user.noaccess)
 *
 * Correct plural form of translated message is returned when $quantity
 * parameter is provided to a method. Otherwise, or on failure just translated
 * version is returned (eg. when string is translated only to one form).
 *
 * When all fails, untranslated (original) string or ID is returned (depends on
 * translation method).
 *
 * Placeholders' resolving is done when needed (see FormatResolver class).
 *
 * Actual translating is done by injected TranslationProvider instance, so
 * storage format depends on concrete implementation.
 *
 * @Flow\Scope("singleton")
 * @api
 * @see \TYPO3\Flow\I18n\FormatResolver
 * @see \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface
 * @see \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
 */
class Translator_Original {

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface
	 */
	protected $translationProvider;

	/**
	 * @var \TYPO3\Flow\I18n\FormatResolver
	 */
	protected $formatResolver;

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * @param \TYPO3\Flow\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface $translationProvider
	 * @return void
	 */
	public function injectTranslationProvider(\TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface $translationProvider) {
		$this->translationProvider = $translationProvider;
	}

	/**
	 * @param \TYPO3\Flow\I18n\FormatResolver $formatResolver
	 * @return void
	 */
	public function injectFormatResolver(\TYPO3\Flow\I18n\FormatResolver $formatResolver) {
		$this->formatResolver = $formatResolver;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 */
	public function injectPluralsReader(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Translates the message given as $originalLabel.
	 *
	 * Searches for a translation in the source as defined by $sourceName
	 * (interpretation depends on concrete translation provider used).
	 *
	 * If any arguments are provided in the $arguments array, they will be inserted
	 * to the translated string (in place of corresponding placeholders, with
	 * format defined by these placeholders).
	 *
	 * If $quantity is provided, correct plural form for provided $locale will
	 * be chosen and used to choose correct translation variant. If $arguments
	 * contains exactly one numeric element, it is automatically used as the
	 * $quantity.
	 *
	 * If no $locale is provided, default system locale will be used.
	 *
	 * @param string $originalLabel Untranslated message
	 * @param array $arguments An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use (NULL for default one)
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return string Translated $originalLabel or $originalLabel itself on failure
	 * @api
	 */
	public function translateByOriginalLabel($originalLabel, array $arguments = array(), $quantity = NULL, \TYPO3\Flow\I18n\Locale $locale = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.Flow') {
		if ($locale === NULL) {
			$locale = $this->localizationService->getConfiguration()->getCurrentLocale();
		}
		$pluralForm = $this->getPluralForm($quantity, $arguments, $locale);

		$translatedMessage = $this->translationProvider->getTranslationByOriginalLabel($originalLabel, $locale, $pluralForm, $sourceName, $packageKey);

		if ($translatedMessage === FALSE) {
			$translatedMessage = $originalLabel;
		}

		if (!empty($arguments)) {
			$translatedMessage = $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
		}

		return $translatedMessage;
	}

	/**
	 * Returns translated string found under the $labelId.
	 *
	 * Searches for a translation in the source as defined by $sourceName
	 * (interpretation depends on concrete translation provider used).
	 *
	 * If any arguments are provided in the $arguments array, they will be inserted
	 * to the translated string (in place of corresponding placeholders, with
	 * format defined by these placeholders).
	 *
	 * If $quantity is provided, correct plural form for provided $locale will
	 * be chosen and used to choose correct translation variant. If $arguments
	 * contains exactly one numeric element, it is automatically used as the
	 * $quantity.
	 *
	 * @param string $labelId Key to use for finding translation
	 * @param array $arguments An array of values to replace placeholders with
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use (NULL for default one)
	 * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
	 * @param string $packageKey Key of the package containing the source file
	 * @return string Translated message or $labelId on failure
	 * @api
	 * @see \TYPO3\Flow\I18n\Translator::translateByOriginalLabel()
	 */
	public function translateById($labelId, array $arguments = array(), $quantity = NULL, \TYPO3\Flow\I18n\Locale $locale = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.Flow') {
		if ($locale === NULL) {
			$locale = $this->localizationService->getConfiguration()->getCurrentLocale();
		}
		$pluralForm = $this->getPluralForm($quantity, $arguments, $locale);

		$translatedMessage = $this->translationProvider->getTranslationById($labelId, $locale, $pluralForm, $sourceName, $packageKey);

		if ($translatedMessage === FALSE) {
			return $labelId;
		} elseif ($arguments !== array()) {
			return $this->formatResolver->resolvePlaceholders($translatedMessage, $arguments, $locale);
		}
		return $translatedMessage;
	}

	/**
	 * Get the plural form to be used.
	 *
	 * If $quantity is non-NULL, the plural form for provided $locale will be
	 * chosen according to it.
	 *
	 * Otherwise, if $arguments contains exactly one numeric element, it is
	 * automatically used as the $quantity.
	 *
	 * In all other cases, NULL is returned.
	 *
	 * @param mixed $quantity
	 * @param array $arguments
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @return string
	 */
	protected function getPluralForm($quantity, array $arguments, Locale $locale) {
		if (!is_numeric($quantity)) {
			if (count($arguments) === 1) {
				return is_numeric(current($arguments)) ? $this->pluralsReader->getPluralForm(current($arguments), $locale) : NULL;
			} else {
				return NULL;
			}
		} else {
			return $this->pluralsReader->getPluralForm($quantity, $locale);
		}
	}
}

namespace TYPO3\Flow\I18n;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A class for translating messages
 * 
 * Messages (labels) can be translated in two modes:
 * - by original label: untranslated label is used as a key
 * - by ID: string identifier is used as a key (eg. user.noaccess)
 * 
 * Correct plural form of translated message is returned when $quantity
 * parameter is provided to a method. Otherwise, or on failure just translated
 * version is returned (eg. when string is translated only to one form).
 * 
 * When all fails, untranslated (original) string or ID is returned (depends on
 * translation method).
 * 
 * Placeholders' resolving is done when needed (see FormatResolver class).
 * 
 * Actual translating is done by injected TranslationProvider instance, so
 * storage format depends on concrete implementation.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Translator extends Translator_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Translator') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Translator', $this);
		if ('TYPO3\Flow\I18n\Translator' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Translator') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Translator', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Translator');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Translator', $propertyName, 'transient')) continue;
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
		$this->injectLocalizationService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Service'));
		$this->injectTranslationProvider(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface'));
		$this->injectFormatResolver(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\FormatResolver'));
		$this->injectPluralsReader(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Cldr\Reader\PluralsReader'));
	}
}
#