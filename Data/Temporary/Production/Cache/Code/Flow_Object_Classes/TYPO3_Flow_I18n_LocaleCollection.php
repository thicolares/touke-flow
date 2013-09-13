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
 * The LocaleCollection class contains all locales available in current
 * Flow installation, and describes hierarchical relations between them.
 *
 * This class maintans a hierarchical relation between locales. For
 * example, a locale "en_GB" will be a child of a locale "en".
 *
 * @Flow\Scope("singleton")
 */
class LocaleCollection_Original {

	/**
	 * This array contains all locales added to this collection.
	 *
	 * The values are Locale objects, and the keys are these locale's tags.
	 *
	 * @var array<\TYPO3\Flow\I18n\Locale>
	 */
	protected $localeCollection = array();

	/**
	 * This array contains a parent Locale objects for given locale.
	 *
	 * "Searching" is done by the keys, which are locale tags. The key points to
	 * the value which is a parent Locale object. If it's not set, there is no
	 * parent for given locale, or no parent was searched before.
	 *
	 * @var array<\TYPO3\Flow\I18n\Locale>
	 */
	protected $localeParentCollection = array();

	/**
	 * Adds a locale to the collection.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The Locale to be inserted
	 * @return boolean FALSE when same locale was already inserted before
	 */
	public function addLocale(\TYPO3\Flow\I18n\Locale $locale) {
		if (isset($this->localeCollection[(string)$locale])) {
			return FALSE;
		}

			// We need to invalidate the parent's array as it could be inaccurate
		$this->localeParentCollection = array();

		$this->localeCollection[(string)$locale] = $locale;
		return TRUE;
	}

	/**
	 * Returns a parent Locale object of the locale provided.
	 *
	 * The parent is a locale which is more generic than the one given as
	 * parameter. For example, the parent for locale en_GB will be locale en, of
	 * course if it exists in the locale tree of available locales.
	 *
	 * This method returns NULL when no parent locale is available, or when
	 * Locale object provided is not in the tree (ie it's not in a group of
	 * available locales).
	 *
	 * Note: to find a best-matching locale to one which doesn't exist in the
	 * system, please use findBestMatchingLocale() method of this class.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The Locale to search parent for
	 * @return mixed Existing \TYPO3\Flow\I18n\Locale instance or NULL on failure
	 */
	public function getParentLocaleOf(\TYPO3\Flow\I18n\Locale $locale) {
		$localeIdentifier = (string)$locale;

		if (!isset($this->localeCollection[$localeIdentifier])) {
			return NULL;
		}

		if (isset($this->localeParentCollection[$localeIdentifier])) {
			return $this->localeParentCollection[$localeIdentifier];
		}

		$parentLocaleIdentifier = $localeIdentifier;
		do {
				// Remove the last (most specific) part of the locale tag
			$parentLocaleIdentifier = substr($parentLocaleIdentifier, 0, (int)strrpos($parentLocaleIdentifier, '_'));

			if (isset($this->localeCollection[$parentLocaleIdentifier])) {
				return $this->localeParentCollection[$localeIdentifier] = $this->localeCollection[$parentLocaleIdentifier];
			}
		} while (strrpos($parentLocaleIdentifier, '_') !== FALSE);

		return NULL;
	}

	/**
	 * Returns Locale object which represents one of locales installed and which
	 * is most similar to the "template" Locale object given as parameter.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The "template" locale to be matched
	 * @return mixed Existing \TYPO3\Flow\I18n\Locale instance on success, NULL on failure
	 */
	public function findBestMatchingLocale(\TYPO3\Flow\I18n\Locale $locale) {
		$localeIdentifier = (string)$locale;

		if (isset($this->localeCollection[$localeIdentifier])) {
			return $this->localeCollection[$localeIdentifier];
		}

		$parentLocaleIdentifier = $localeIdentifier;
		do {
				// Remove the last (most specific) part of the locale tag
			$parentLocaleIdentifier = substr($parentLocaleIdentifier, 0, (int)strrpos($parentLocaleIdentifier, '_'));

			if (isset($this->localeCollection[$parentLocaleIdentifier])) {
				return $this->localeCollection[$parentLocaleIdentifier];
			}
		} while (strrpos($parentLocaleIdentifier, '_') !== FALSE);

		return NULL;
	}
}

namespace TYPO3\Flow\I18n;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The LocaleCollection class contains all locales available in current
 * Flow installation, and describes hierarchical relations between them.
 * 
 * This class maintans a hierarchical relation between locales. For
 * example, a locale "en_GB" will be a child of a locale "en".
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class LocaleCollection extends LocaleCollection_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\LocaleCollection') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\LocaleCollection', $this);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\LocaleCollection') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\LocaleCollection', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\LocaleCollection');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\LocaleCollection', $propertyName, 'transient')) continue;
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