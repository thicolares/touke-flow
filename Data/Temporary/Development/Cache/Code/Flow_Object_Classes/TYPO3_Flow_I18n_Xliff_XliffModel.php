<?php
namespace TYPO3\Flow\I18n\Xliff;

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
 * A model representing data from one XLIFF file.
 *
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 *
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 *
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu
 */
class XliffModel_Original {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Concrete XML parser which is set by more specific model extending this
	 * class.
	 *
	 * @var \TYPO3\Flow\I18n\Xliff\XliffParser
	 */
	protected $xmlParser;

	/**
	 * Absolute path to the file which is represented by this class instance.
	 *
	 * @var string
	 */
	protected $sourcePath;

	/**
	 * @var \TYPO3\Flow\I18n\Locale
	 */
	protected $locale;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Parsed data (structure depends on concrete model).
	 *
	 * @var array
	 */
	protected $xmlParsedData;

	/**
	 * @param string $sourcePath
	 * @param \TYPO3\Flow\I18n\Locale $locale The locale represented by the file
	 */
	public function __construct($sourcePath, \TYPO3\Flow\I18n\Locale $locale) {
		$this->sourcePath = $sourcePath;
		$this->locale = $locale;
	}

	/**
	 * Injects the Flow_I18n_XmlModelCache cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Xliff\XliffParser $parser
	 * @return void
	 */
	public function injectParser(\TYPO3\Flow\I18n\Xliff\XliffParser $parser) {
		$this->xmlParser = $parser;
	}

	/**
	 * When it's called, XML file is parsed (using parser set in $xmlParser)
	 * or cache is loaded, if available.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has(md5($this->sourcePath))) {
			$this->xmlParsedData = $this->cache->get(md5($this->sourcePath));
		} else {
			$this->xmlParsedData = $this->xmlParser->getParsedData($this->sourcePath);
			$this->cache->set(md5($this->sourcePath), $this->xmlParsedData);
		}
	}

	/**
	 * Returns translated label ("target" tag in XLIFF) from source-target
	 * pair where "source" tag equals to $source parameter.
	 *
	 * @param string $source Label in original language ("source" tag in XLIFF)
	 * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTargetBySource($source, $pluralFormIndex = 0) {
		foreach ($this->xmlParsedData['translationUnits'] as $translationUnit) {
				// $source is always singular (or only) form, so compare with index 0
			if (!isset($translationUnit[0]) || $translationUnit[0]['source'] !== $source) {
				continue;
			}

			if (count($translationUnit) <= $pluralFormIndex) {
				$this->systemLogger->log('The plural form index "' . $pluralFormIndex . '" for the source translation "' . $source . '"  in ' . $this->sourcePath . ' is not available.', LOG_WARNING);
				return FALSE;
			}

			return $translationUnit[$pluralFormIndex]['target'] ?: FALSE;
		}

		return FALSE;
	}

	/**
	 * Returns translated label ("target" tag in XLIFF) for the id given.
	 * Id is compared with "id" attribute of "trans-unit" tag (see XLIFF
	 * specification for details).
	 *
	 * @param string $transUnitId The "id" attribute of "trans-unit" tag in XLIFF
	 * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
	 * @return mixed Translated label or FALSE on failure
	 */
	public function getTargetByTransUnitId($transUnitId, $pluralFormIndex = 0) {
		if (!isset($this->xmlParsedData['translationUnits'][$transUnitId])) {
			$this->systemLogger->log('No trans-unit element with the id "' . $transUnitId . '" was found in ' . $this->sourcePath . '. Either this translation has been removed or the id in the code or template referring to the translation is wrong.', LOG_WARNING);
			return FALSE;
		}

		if (!isset($this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex])) {
			$this->systemLogger->log('The plural form index "' . $pluralFormIndex . '" for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->sourcePath . ' is not available.', LOG_WARNING);
			return FALSE;
		}

		if ($this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['target']) {
			return $this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['target'];
		} elseif ($this->locale->getLanguage() === $this->xmlParsedData['sourceLocale']->getLanguage()) {
			return $this->xmlParsedData['translationUnits'][$transUnitId][$pluralFormIndex]['source'] ?: FALSE;
		} else {
			$this->systemLogger->log('The target translation was empty and the source translation language (' . $this->xmlParsedData['sourceLocale']->getLanguage() . ') does not match the current locale (' . $this->locale->getLanguage() . ') for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->sourcePath, LOG_WARNING);
			return FALSE;
		}
	}
}

namespace TYPO3\Flow\I18n\Xliff;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A model representing data from one XLIFF file.
 * 
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 * 
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 */
class XliffModel extends XliffModel_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param string $sourcePath
	 * @param \TYPO3\Flow\I18n\Locale $locale The locale represented by the file
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(1, $arguments)) $arguments[1] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Locale');
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $sourcePath in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $locale in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\I18n\Xliff\XliffModel' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
	}

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Xliff\XliffModel');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Xliff\XliffModel', $propertyName, 'transient')) continue;
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
		$this->injectCache(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_I18n_XmlModelCache'));
		$this->injectParser(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Xliff\XliffParser'));
		$systemLogger_reference = &$this->systemLogger;
		$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SystemLoggerInterface');
		if ($this->systemLogger === NULL) {
			$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('6d57d95a1c3cd7528e3e6ea15012dac8', $systemLogger_reference);
			if ($this->systemLogger === NULL) {
				$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('6d57d95a1c3cd7528e3e6ea15012dac8',  $systemLogger_reference, 'TYPO3\Flow\Log\SystemLoggerInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'); });
			}
		}
	}
}
#