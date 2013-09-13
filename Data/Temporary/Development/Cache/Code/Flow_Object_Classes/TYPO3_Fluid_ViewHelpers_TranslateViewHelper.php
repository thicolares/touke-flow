<?php
namespace TYPO3\Fluid\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Returns translated message using source message or key ID.
 *
 * Also replaces all placeholders with formatted versions of provided values.
 *
 * = Examples =
 *
 * <code title="Translation by id">
 * <f:translate id="user.unregistered">Unregistered User</f:translate>
 * </code>
 * <output>
 * translation of label with the id "user.unregistered" and a fallback to "Unregistered User"
 * </output>
 *
 * <code title="Inline notation">
 * {f:translate(id: 'some.label.id', default: 'fallback result')}
 * </code>
 * <output>
 * translation of label with the id "some.label.id" and a fallback to "fallback result"
 * </output>
 *
 * <code title="Custom source and locale">
 * <f:translate id="some.label.id" somesource="SomeLabelsCatalog" locale="de_DE"/>
 * </code>
 * <output>
 * translation from custom source "SomeLabelsCatalog" for locale "de_DE"
 * </output>
 *
 * <code title="Custom source from other package">
 * <f:translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>
 * </code>
 * <output>
 * translation from custom source "LabelsCatalog" in "OtherPackage"
 * </output>
 *
 * <code title="Arguments">
 * <f:translate arguments="{0: 'foo', 1: '99.9'}">Untranslated {0} and {1,number}</f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated foo and 99.9"
 * </output>
 *
 * <code title="Translation by label">
 * <f:translate>Untranslated label</f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated label"
 * </output>
 *
 */
class TranslateViewHelper_Original extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\Flow\I18n\Translator
	 * @Flow\Inject
	 */
	protected $translator;

	/**
	 * Renders the translated label.
	 *
	 * Replaces all placeholders with corresponding values if they exist in the
	 * translated label.
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @param array $arguments Numerically indexed array of values to be inserted into placeholders
	 * @param string $source Name of file with translations
	 * @param string $package Target package key. If not set, the current package key will be used
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param string $locale An identifier of locale to use (NULL for use the default locale)
	 * @return string Translated label or source label / ID key
	 * @throws \TYPO3\Fluid\Core\ViewHelper\Exception
	 */
	public function render($id = NULL, $value = NULL, array $arguments = array(), $source = 'Main', $package = NULL, $quantity = NULL, $locale = NULL) {
		$localeObject = NULL;
		if ($locale !== NULL) {
			try {
				$localeObject = new \TYPO3\Flow\I18n\Locale($locale);
			} catch (\TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException $e) {
				throw new \TYPO3\Fluid\Core\ViewHelper\Exception('"' . $locale . '" is not a valid locale identifier.' , 1279815885);
			}
		}
		if ($package === NULL) $package = $this->controllerContext->getRequest()->getControllerPackageKey();
		$originalLabel = $value === NULL ? $this->renderChildren() : $value;

		if ($id === NULL) {
			return $this->translator->translateByOriginalLabel($originalLabel, $arguments, $quantity, $localeObject, $source, $package);
		} else {
			$translation = $this->translator->translateById($id, $arguments, $quantity, $localeObject, $source, $package);
			if ($translation === $id) {
				if ($originalLabel) {
					return $this->translator->translateByOriginalLabel($originalLabel, $arguments, $quantity, $localeObject, $source, $package);
				} else {
					return $id;
				}
			} else {
				return $translation;
			}
		}
	}
}

namespace TYPO3\Fluid\ViewHelpers;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Returns translated message using source message or key ID.
 * 
 * Also replaces all placeholders with formatted versions of provided values.
 * 
 * = Examples =
 * 
 * <code title="Translation by id">
 * <f:translate id="user.unregistered">Unregistered User</f:translate>
 * </code>
 * <output>
 * translation of label with the id "user.unregistered" and a fallback to "Unregistered User"
 * </output>
 * 
 * <code title="Inline notation">
 * {f:translate(id: 'some.label.id', default: 'fallback result')}
 * </code>
 * <output>
 * translation of label with the id "some.label.id" and a fallback to "fallback result"
 * </output>
 * 
 * <code title="Custom source and locale">
 * <f:translate id="some.label.id" somesource="SomeLabelsCatalog" locale="de_DE"/>
 * </code>
 * <output>
 * translation from custom source "SomeLabelsCatalog" for locale "de_DE"
 * </output>
 * 
 * <code title="Custom source from other package">
 * <f:translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>
 * </code>
 * <output>
 * translation from custom source "LabelsCatalog" in "OtherPackage"
 * </output>
 * 
 * <code title="Arguments">
 * <f:translate arguments="{0: 'foo', 1: '99.9'}">Untranslated {0} and {1,number}</f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated foo and 99.9"
 * </output>
 * 
 * <code title="Translation by label">
 * <f:translate>Untranslated label</f:translate>
 * </code>
 * <output>
 * translation of the label "Untranslated label"
 * </output>
 */
class TranslateViewHelper extends TranslateViewHelper_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Fluid\ViewHelpers\TranslateViewHelper' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
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
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\ViewHelpers\TranslateViewHelper');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', $propertyName, 'transient')) continue;
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
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectSystemLogger(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$translator_reference = &$this->translator;
		$this->translator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\I18n\Translator');
		if ($this->translator === NULL) {
			$this->translator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('2f8185fb502197b0c84bc82ea7cd1c44', $translator_reference);
			if ($this->translator === NULL) {
				$this->translator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('2f8185fb502197b0c84bc82ea7cd1c44',  $translator_reference, 'TYPO3\Flow\I18n\Translator', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\I18n\Translator'); });
			}
		}
	}
}
#