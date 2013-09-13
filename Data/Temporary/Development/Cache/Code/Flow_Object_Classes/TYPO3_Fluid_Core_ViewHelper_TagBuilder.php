<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Tag builder. Can be easily accessed in AbstractTagBasedViewHelper
 *
 * @api
 */
class TagBuilder_Original {

	/**
	 * Name of the Tag to be rendered
	 *
	 * @var string
	 */
	protected $tagName = '';

	/**
	 * Content of the tag to be rendered
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Attributes of the tag to be rendered
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Specifies whether this tag needs a closing tag.
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @var boolean
	 */
	protected $forceClosingTag = FALSE;

	/**
	 * Constructor
	 *
	 * @param string $tagName name of the tag to be rendered
	 * @param string $tagContent content of the tag to be rendered
	 * @api
	 */
	public function __construct($tagName = '', $tagContent = '') {
		$this->setTagName($tagName);
		$this->setContent($tagContent);
	}

	/**
	 * Sets the tag name
	 *
	 * @param string $tagName name of the tag to be rendered
	 * @return void
	 * @api
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
	}

	/**
	 * Gets the tag name
	 *
	 * @return string tag name of the tag to be rendered
	 * @api
	 */
	public function getTagName() {
		return $this->tagName;
	}

	/**
	 * Sets the content of the tag
	 *
	 * @param string $tagContent content of the tag to be rendered
	 * @return void
	 * @api
	 */
	public function setContent($tagContent) {
		$this->content = $tagContent;
	}

	/**
	 * Gets the content of the tag
	 *
	 * @return string content of the tag to be rendered
	 * @api
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Returns TRUE if tag contains content, otherwise FALSE
	 *
	 * @return boolean TRUE if tag contains text, otherwise FALSE
	 * @api
	 */
	public function hasContent() {
		if ($this->content === NULL) {
			return FALSE;
		}
		return $this->content !== '';
	}

	/**
	 * Set this to TRUE to force a closing tag
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @param boolean $forceClosingTag
	 * @api
	 */
	public function forceClosingTag($forceClosingTag) {
		$this->forceClosingTag = $forceClosingTag;
	}

	/**
	 * Returns TRUE if the tag has an attribute with the given name
	 *
	 * @param string $attributeName name of the attribute
	 * @return boolean TRUE if the tag has an attribute with the given name, otherwise FALSE
	 * @api
	 */
	public function hasAttribute($attributeName) {
		return array_key_exists($attributeName, $this->attributes);
	}

	/**
	 * Get an attribute from the $attributes-collection
	 *
	 * @param string $attributeName name of the attribute
	 * @return string The attribute value or NULL if the attribute is not registered
	 * @api
	 */
	public function getAttribute($attributeName) {
		if (!$this->hasAttribute($attributeName)) {
			return NULL;
		}
		return $this->attributes[$attributeName];
	}

	/**
	 * Get all attribute from the $attributes-collection
	 *
	 * @return array Attributes indexed by attribute name
	 * @api
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Adds an attribute to the $attributes-collection
	 *
	 * @param string $attributeName name of the attribute to be added to the tag
	 * @param string $attributeValue attribute value
	 * @param boolean $escapeSpecialCharacters apply htmlspecialchars to attribute value
	 * @return void
	 * @api
	 */
	public function addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters = TRUE) {
		if ($escapeSpecialCharacters) {
			$attributeValue = htmlspecialchars($attributeValue);
		}
		$this->attributes[$attributeName] = $attributeValue;
	}

	/**
	 * Adds attributes to the $attributes-collection
	 *
	 * @param array $attributes collection of attributes to add. key = attribute name, value = attribute value
	 * @param boolean $escapeSpecialCharacters apply htmlspecialchars to attribute values#
	 * @return void
	 * @api
	 */
	public function addAttributes(array $attributes, $escapeSpecialCharacters = TRUE) {
		foreach ($attributes as $attributeName => $attributeValue) {
			$this->addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters);
		}
	}

	/**
	 * Removes an attribute from the $attributes-collection
	 *
	 * @param string $attributeName name of the attribute to be removed from the tag
	 * @return void
	 * @api
	 */
	public function removeAttribute($attributeName) {
		unset($this->attributes[$attributeName]);
	}

	/**
	 * Resets the TagBuilder by setting all members to their default value
	 *
	 * @return void
	 * @api
	 */
	public function reset() {
		$this->tagName = '';
		$this->content = '';
		$this->attributes = array();
		$this->forceClosingTag = FALSE;
	}

	/**
	 * Renders and returns the tag
	 *
	 * @return string
	 * @api
	 */
	public function render() {
		if (empty($this->tagName)) {
			return '';
		}
		$output = '<' . $this->tagName;
		foreach ($this->attributes as $attributeName => $attributeValue) {
			$output .= ' ' . $attributeName . '="' . $attributeValue . '"';
		}
		if ($this->hasContent() || $this->forceClosingTag) {
			$output .= '>' . $this->content . '</' . $this->tagName . '>';
		} else {
			$output .= ' />';
		}
		return $output;
	}
}
namespace TYPO3\Fluid\Core\ViewHelper;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Tag builder. Can be easily accessed in AbstractTagBasedViewHelper
 */
class TagBuilder extends TagBuilder_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param string $tagName name of the tag to be rendered
	 * @param string $tagContent content of the tag to be rendered
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = '';
		if (!array_key_exists(1, $arguments)) $arguments[1] = '';
		call_user_func_array('parent::__construct', $arguments);
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
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\Core\ViewHelper\TagBuilder');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\Core\ViewHelper\TagBuilder', $propertyName, 'transient')) continue;
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