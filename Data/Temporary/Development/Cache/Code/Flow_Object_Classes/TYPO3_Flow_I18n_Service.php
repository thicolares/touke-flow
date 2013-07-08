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
use TYPO3\Flow\Utility\Files;

/**
 * A Service which provides further information about a given locale
 * and the current state of the i18n and L10n components.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Service_Original {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @Flow\Inject(lazy=false)
	 * @var \TYPO3\Flow\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @Flow\Inject(lazy=false)
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var \TYPO3\Flow\I18n\Configuration
	 */
	protected $configuration;

	/**
	 * The base path to use in filesystem operations. It is changed only in tests.
	 *
	 * @var string
	 */
	protected $localeBasePath = 'resource://';

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['i18n'];
	}

	/**
	 * Initializes the locale service
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->configuration = new Configuration($this->settings['defaultLocale']);
		$this->configuration->setFallbackRule($this->settings['fallbackRule']);

		if ($this->cache->has('availableLocales')) {
			$this->localeCollection = $this->cache->get('availableLocales');
		} else {
			$this->generateAvailableLocalesCollectionByScanningFilesystem();
			$this->cache->set('availableLocales', $this->localeCollection);
		}
	}

	/**
	 * @return \TYPO3\Flow\I18n\Configuration
	 * @api
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Returns the path to the existing localized version of file given.
	 *
	 * Searching is done for the current locale if no $locale parameter is
	 * provided. The search is done according to the configured fallback
	 * rule.
	 *
	 * If parameter $strict is provided, searching is done only for the
	 * provided / current locale (without searching of files localized for
	 * more generic locales).
	 *
	 * If no localized version of file is found, $filepath is returned without
	 * any change.
	 *
	 * @param string $pathAndFilename Path to the file
	 * @param \TYPO3\Flow\I18n\Locale $locale Desired locale of localized file
	 * @param boolean $strict Whether to match only provided locale (TRUE) or search for best-matching locale (FALSE)
	 * @return array Path to the localized file (or $filename when no localized file was found) and the matched locale
	 * @see Configuration::setFallbackRule()
	 * @api
	 */
	public function getLocalizedFilename($pathAndFilename, Locale $locale = NULL, $strict = FALSE) {
		if ($locale === NULL) {
			$locale = $this->configuration->getCurrentLocale();
		}

		$filename = basename($pathAndFilename);
		if ((strpos($filename, '.')) !== FALSE) {
			$dotPosition = strrpos($pathAndFilename, '.');
			$pathAndFilenameWithoutExtension = substr($pathAndFilename, 0, $dotPosition);
			$extension = substr($pathAndFilename, $dotPosition);
		} else {
			$pathAndFilenameWithoutExtension = $pathAndFilename;
			$extension = '';
		}

		if ($strict === TRUE) {
			$possibleLocalizedFilename = $pathAndFilenameWithoutExtension . '.' . (string)$locale . $extension;
			if (file_exists($possibleLocalizedFilename)) {
				return array($possibleLocalizedFilename, $locale);
			}
		} else {
			foreach ($this->getLocaleChain($locale) as $localeIdentifier => $locale) {
				$possibleLocalizedFilename = $pathAndFilenameWithoutExtension . '.' . $localeIdentifier . $extension;
				if (file_exists($possibleLocalizedFilename)) {
					return array($possibleLocalizedFilename, $locale);
				}
			}
		}
		return array($pathAndFilename, $locale);
	}

	/**
	 * Returns the path to the existing localized version of file given.
	 *
	 * Searching is done for the current locale if no $locale parameter is
	 * provided. The search is done according to the configured fallback
	 * rule.
	 *
	 * If parameter $strict is provided, searching is done only for the
	 * provided / current locale (without searching of files localized for
	 * more generic locales).
	 *
	 * If no localized version of file is found, $filepath is returned without
	 * any change.
	 *
	 * @param string $path Base directory to the translation files
	 * @param string $sourceName name of the translation source
	 * @param \TYPO3\Flow\I18n\Locale $locale Desired locale of XLIFF file
	 * @return array Path to the localized file (or $filename when no localized file was found) and the matched locale
	 * @see Configuration::setFallbackRule()
	 * @api
	 */
	public function getXliffFilenameAndPath($path, $sourceName, Locale $locale = NULL) {
		if ($locale === NULL) {
			$locale = $this->configuration->getCurrentLocale();
		}

		foreach ($this->getLocaleChain($locale) as $localeIdentifier => $locale) {
			$possibleXliffFilename = Files::concatenatePaths(array($path, $localeIdentifier, $sourceName . '.xlf'));
			if (file_exists($possibleXliffFilename)) {
				return array($possibleXliffFilename, $locale);
			}
		}
		return array(FALSE, $locale);
	}

	/**
	 * Build a chain of locale objects according to the fallback rule and
	 * the available locales.
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @return array
	 */
	public function getLocaleChain(Locale $locale) {
		$fallBackRule = $this->configuration->getFallbackRule();
		$localeChain = array((string)$locale => $locale);

		if ($fallBackRule['strict'] === TRUE) {
			foreach ($fallBackRule['order'] as $localeIdentifier) {
				$localeChain[$localeIdentifier] = new Locale($localeIdentifier);
			}
		} else {
			$locale = $this->findBestMatchingLocale($locale);
			while ($locale !== NULL) {
				$localeChain[(string)$locale] = $locale;
				$locale = $this->getParentLocaleOf($locale);
			}
			foreach ($fallBackRule['order'] as $localeIdentifier) {
				$locale = new Locale($localeIdentifier);
				$locale = $this->findBestMatchingLocale($locale);
				while ($locale !== NULL) {
					$localeChain[(string)$locale] = $locale;
					$locale = $this->getParentLocaleOf($locale);
				}
			}
		}
		$locale = $this->configuration->getDefaultLocale();
		$localeChain[(string)$locale] = $locale;

		return $localeChain;
	}

	/**
	 * Returns a parent Locale object of the locale provided.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The Locale to search parent for
	 * @return \TYPO3\Flow\I18n\Locale Existing \TYPO3\Flow\I18n\Locale instance or NULL on failure
	 * @api
	 */
	public function getParentLocaleOf(\TYPO3\Flow\I18n\Locale $locale) {
		return $this->localeCollection->getParentLocaleOf($locale);
	}

	/**
	 * Returns Locale object which is the most similar to the "template" Locale
	 * object given as parameter, from the collection of locales available in
	 * the current Flow installation.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale The "template" Locale to be matched
	 * @return mixed Existing \TYPO3\Flow\I18n\Locale instance on success, NULL on failure
	 * @api
	 */
	public function findBestMatchingLocale(\TYPO3\Flow\I18n\Locale $locale) {
		return $this->localeCollection->findBestMatchingLocale($locale);
	}

	/**
	 * Finds all Locale objects representing locales available in the
	 * Flow installation. This is done by scanning all Private and Public
	 * resource files of all active packages, in order to find localized files.
	 *
	 * Localized files have a locale identifier added before their extension
	 * (or at the end of filename, if no extension exists). For example, a
	 * localized file for foobar.png, can be foobar.en.png, fobar.en_GB.png, etc.
	 *
	 * Just one localized resource file causes the corresponding locale to be
	 * regarded as available (installed, supported).
	 *
	 * Note: result of this method invocation is cached
	 *
	 * @return void
	 */
	protected function generateAvailableLocalesCollectionByScanningFilesystem() {
		foreach ($this->packageManager->getActivePackages() as $activePackage) {

			$packageResourcesPath = $this->localeBasePath . $activePackage->getPackageKey() . '/';
			if (!is_dir($packageResourcesPath)) {
				continue;
			}

			$directoryIterator = new \RecursiveDirectoryIterator($packageResourcesPath, \RecursiveDirectoryIterator::UNIX_PATHS);
			$recursiveIteratorIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($recursiveIteratorIterator as $fileOrDirectory) {
				if ($fileOrDirectory->isFile()) {
					$localeIdentifier = Utility::extractLocaleTagFromFilename($fileOrDirectory->getFilename());
					if ($localeIdentifier !== FALSE) {
						$this->localeCollection->addLocale(new Locale($localeIdentifier));
					}
				}
			}
		}
	}

}

namespace TYPO3\Flow\I18n;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Service which provides further information about a given locale
 * and the current state of the i18n and L10n components.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Service extends Service_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Service') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Service', $this);
		if ('TYPO3\Flow\I18n\Service' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Service') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Service', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Service');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Service', $propertyName, 'transient')) continue;
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
		$cache_reference = &$this->cache;
		$this->cache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('');
		if ($this->cache === NULL) {
			$this->cache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('fa4c49ea764964894ed3d58676677678', $cache_reference);
			if ($this->cache === NULL) {
				$this->cache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('fa4c49ea764964894ed3d58676677678',  $cache_reference, '', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_I18n_AvailableLocalesCache'); });
			}
		}
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$packageManager_reference = &$this->packageManager;
		$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Package\PackageManagerInterface');
		if ($this->packageManager === NULL) {
			$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('aad0cdb65adb124cf4b4d16c5b42256c', $packageManager_reference);
			if ($this->packageManager === NULL) {
				$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('aad0cdb65adb124cf4b4d16c5b42256c',  $packageManager_reference, 'TYPO3\Flow\Package\PackageManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'); });
			}
		}
		$this->localeCollection = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\LocaleCollection');
	}
}
#