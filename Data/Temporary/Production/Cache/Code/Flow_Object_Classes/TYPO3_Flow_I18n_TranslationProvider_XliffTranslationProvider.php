<?php
namespace TYPO3\Flow\I18n\TranslationProvider;

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
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @Flow\Scope("singleton")
 */
class XliffTranslationProvider_Original implements \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface {

	/**
	 * An absolute path to the directory where translation files reside.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'Private/Translations/';

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
	 */
	protected $pluralsReader;

	/**
	 * A collection of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['filename'] => $model,
	 *
	 * @var array<\TYPO3\Flow\I18n\Xliff\XliffModel>
	 */
	protected $models;

	/**
	 * @param \TYPO3\Flow\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader
	 * @return void
	 */
	public function injectPluralsReader(\TYPO3\Flow\I18n\Cldr\Reader\PluralsReader $pluralsReader) {
		$this->pluralsReader = $pluralsReader;
	}

	/**
	 * Returns translated label of $originalLabel from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $originalLabel Label used as a key in order to find translation
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
	 */
	public function getTranslationByOriginalLabel($originalLabel, \TYPO3\Flow\I18n\Locale $locale, $pluralForm = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.Flow') {
		$model = $this->getModel($packageKey, $sourceName, $locale);

		if ($pluralForm !== NULL) {
			$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

			if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
				throw new \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
			}
				// We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
			$pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
		} else {
			$pluralFormIndex = 0;
		}

		return $model->getTargetBySource($originalLabel, $pluralFormIndex);
	}

	/**
	 * Returns label for a key ($labelId) from a file defined by $sourceName.
	 *
	 * Chooses particular form of label if available and defined in $pluralForm.
	 *
	 * @param string $labelId Key used to find translated label
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param string $pluralForm One of RULE constants of PluralsReader
	 * @param string $sourceName A relative path to the filename with translations (labels' catalog)
	 * @param string $packageKey Key of the package containing the source file
	 * @return mixed Translated label or FALSE on failure
	 * @throws \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException
	 */
	public function getTranslationById($labelId, \TYPO3\Flow\I18n\Locale $locale, $pluralForm = NULL, $sourceName = 'Main', $packageKey = 'TYPO3.Flow') {
		$model = $this->getModel($packageKey, $sourceName, $locale);

		if ($pluralForm !== NULL) {
			$pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

			if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
				throw new \TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
			}
				// We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
			$pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
		} else {
			$pluralFormIndex = 0;
		}

		return $model->getTargetByTransUnitId($labelId, $pluralFormIndex);
	}

	/**
	 * Returns a XliffModel instance representing desired XLIFF file.
	 *
	 * Will return existing instance if a model for given $sourceName was already
	 * requested before. Returns FALSE when $sourceName doesn't point to existing
	 * file.
	 *
	 * @param string $packageKey Key of the package containing the source file
	 * @param string $sourceName Relative path to existing CLDR file
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale object
	 * @return \TYPO3\Flow\I18n\Xliff\XliffModel New or existing instance
	 * @throws \TYPO3\Flow\I18n\Exception
	 */
	protected function getModel($packageKey, $sourceName, \TYPO3\Flow\I18n\Locale $locale) {
		$sourcePath = \TYPO3\Flow\Utility\Files::concatenatePaths(array('resource://' . $packageKey, $this->xliffBasePath));
		list($sourcePath, $foundLocale) = $this->localizationService->getXliffFilenameAndPath($sourcePath, $sourceName, $locale);

		if ($sourcePath === FALSE) {
			throw new \TYPO3\Flow\I18n\Exception('No XLIFF file is available for ' . $packageKey . '::' . $sourceName . '::' . $locale . ' in the locale chain.', 1334759591);
		}
		if (isset($this->models[$sourcePath])) {
			return $this->models[$sourcePath];
		}
		return $this->models[$sourcePath] = new \TYPO3\Flow\I18n\Xliff\XliffModel($sourcePath, $foundLocale);
	}
}

namespace TYPO3\Flow\I18n\TranslationProvider;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class XliffTranslationProvider extends XliffTranslationProvider_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider', $this);
		if (get_class($this) === 'TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface', $this);
		if ('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider', $this);
		if (get_class($this) === 'TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider', $propertyName, 'transient')) continue;
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
		$this->injectPluralsReader(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Cldr\Reader\PluralsReader'));
	}
}
#