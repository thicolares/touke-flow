<?php
namespace TYPO3\Flow\I18n\Cldr;

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
 * The CldrRepository class
 *
 * CldrRepository manages CldrModel instances across the framework, so there is
 * only one instance of CldrModel for every unique CLDR data file or file group.
 *
 * @Flow\Scope("singleton")
 */
class CldrRepository_Original {

	/**
	 * An absolute path to the directory where CLDR resides. It is changed only
	 * in tests.
	 *
	 * @var string
	 */
	protected $cldrBasePath = 'resource://TYPO3.Flow/Private/I18n/CLDR/Sources/';

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * An array of models requested at least once in current request.
	 *
	 * This is an associative array with pairs as follow:
	 * ['path']['locale'] => $model,
	 *
	 * where 'path' is a file or directory path and 'locale' is a Locale object.
	 * For models representing one CLDR file, the 'path' points to a file and
	 * 'locale' is not used. For models representing few CLDR files connected
	 * with hierarchical relation, 'path' points to a directory where files
	 * reside and 'locale' is used to define which files are included in the
	 * relation (e.g. for locale 'en_GB' files would be: root + en + en_GB).
	 *
	 * @var array<\TYPO3\Flow\I18n\Cldr\CldrModel>
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
	 * Returns an instance of CldrModel which represents CLDR file found under
	 * specified path.
	 *
	 * Will return existing instance if a model for given $filename was already
	 * requested before. Returns FALSE when $filename doesn't point to existing
	 * file.
	 *
	 * @param string $filename Relative (from CLDR root) path to existing CLDR file
	 * @return \TYPO3\Flow\I18n\Cldr\CldrModel|boolean A \TYPO3\Flow\I18n\Cldr\CldrModel instance or FALSE on failure
	 */
	public function getModel($filename) {
		$filename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->cldrBasePath, $filename . '.xml'));

		if (isset($this->models[$filename])) {
			return $this->models[$filename];
		}

		if (!is_file($filename)) {
			return FALSE;
		}

		return $this->models[$filename] = new \TYPO3\Flow\I18n\Cldr\CldrModel(array($filename));
	}

	/**
	 * Returns an instance of CldrModel which represents group of CLDR files
	 * which are related in hierarchy.
	 *
	 * This method finds a group of CLDR files within $directoryPath dir,
	 * for particular Locale. Returned model represents whole locale-chain.
	 *
	 * For example, for locale en_GB, returned model could represent 'en_GB',
	 * 'en', and 'root' CLDR files.
	 *
	 * Returns FALSE when $directoryPath doesn't point to existing directory.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale A locale
	 * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
	 * @return \TYPO3\Flow\I18n\Cldr\CldrModel A \TYPO3\Flow\I18n\Cldr\CldrModel instance or NULL on failure
	 */
	public function getModelForLocale(\TYPO3\Flow\I18n\Locale $locale, $directoryPath = 'main') {
		$directoryPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->cldrBasePath, $directoryPath));

		if (isset($this->models[$directoryPath][(string)$locale])) {
			return $this->models[$directoryPath][(string)$locale];
		}

		if (!is_dir($directoryPath)) {
			return NULL;
		}

		$filesInHierarchy = $this->findLocaleChain($locale, $directoryPath);

		return $this->models[$directoryPath][(string)$locale] = new \TYPO3\Flow\I18n\Cldr\CldrModel($filesInHierarchy);
	}

	/**
	 * Returns absolute paths to CLDR files connected in hierarchy
	 *
	 * For given locale, many CLDR files have to be merged in order to get full
	 * set of CLDR data. For example, for 'en_GB' locale, files 'root', 'en',
	 * and 'en_GB' should be merged.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale A locale
	 * @param string $directoryPath Relative path to existing CLDR directory which contains one file per locale (see 'main' directory in CLDR for example)
	 * @return array<string> Absolute paths to CLDR files in hierarchy
	 */
	protected function findLocaleChain(\TYPO3\Flow\I18n\Locale $locale, $directoryPath) {
		$filesInHierarchy = array(\TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, (string)$locale . '.xml')));

		$localeIdentifier = (string)$locale;
		while ($localeIdentifier = substr($localeIdentifier, 0, (int)strrpos($localeIdentifier, '_'))) {
			$possibleFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, $localeIdentifier . '.xml'));
			if (file_exists($possibleFilename)) {
				array_unshift($filesInHierarchy, $possibleFilename);
			}
		}
		array_unshift($filesInHierarchy, \TYPO3\Flow\Utility\Files::concatenatePaths(array($directoryPath, 'root.xml')));

		return $filesInHierarchy;
	}
}

namespace TYPO3\Flow\I18n\Cldr;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The CldrRepository class
 * 
 * CldrRepository manages CldrModel instances across the framework, so there is
 * only one instance of CldrModel for every unique CLDR data file or file group.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class CldrRepository extends CldrRepository_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Cldr\CldrRepository') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Cldr\CldrRepository', $this);
		if ('TYPO3\Flow\I18n\Cldr\CldrRepository' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Cldr\CldrRepository') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Cldr\CldrRepository', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Cldr\CldrRepository');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Cldr\CldrRepository', $propertyName, 'transient')) continue;
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
	}
}
#