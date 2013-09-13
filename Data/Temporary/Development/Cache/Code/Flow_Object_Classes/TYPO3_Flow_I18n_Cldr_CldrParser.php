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
 * A class which parses CLDR file to simple but useful array representation.
 *
 * Parsed data is an array where keys are nodes from XML file with its attributes
 * (if any). Only distinguishing attributes are taken into account (see [1]).
 * Below are examples of parsed data structure.
 *
 * such XML data:
 * <dates>
 *   <calendars>
 *     <calendar type="gregorian">
 *       <months />
 *     </calendar>
 *     <calendar type="buddhist">
 *       <months />
 *     </calendar>
 *   </calendars>
 * </dates>
 *
 * will be converted to such array:
 * array(
 *   'dates' => array(
 *     'calendars' => array(
 *       'calendar[@type="gregorian"]' => array(
 *         'months' => ''
 *       ),
 *       'calendar[@type="buddhist"]' => array(
 *         'months' => ''
 *       ),
 *     )
 *   )
 * )
 *
 * @Flow\Scope("singleton")
 * @see http://www.unicode.org/reports/tr35/#Inheritance_and_Validity [1]
 */
class CldrParser_Original extends \TYPO3\Flow\I18n\AbstractXmlParser {

	/**
	 * Returns array representation of XML data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed CLDR File
	 * @see \TYPO3\Flow\Xml\AbstractXmlParser::doParsingFromRoot()
	 */
	protected function doParsingFromRoot(\SimpleXMLElement $root) {
		return $this->parseNode($root);
	}

	/**
	 * Returns array representation of XML data, starting from a node pointed by
	 * $node variable.
	 *
	 * Please see the documentation of this class for details about the internal
	 * representation of XML data.
	 *
	 * @param \SimpleXMLElement $node A node to start parsing from
	 * @return mixed An array representing parsed XML node or string value if leaf
	 */
	protected function parseNode(\SimpleXMLElement $node) {
		$parsedNode = array();

		if ($node->count() === 0) {
			return (string)$node;
		}

		foreach ($node->children() as $child) {
			$nameOfChild = $child->getName();

			$parsedChild = $this->parseNode($child);

			if (count($child->attributes()) > 0) {
				$parsedAttributes = '';
				foreach ($child->attributes() as $attributeName => $attributeValue) {
					if ($this->isDistinguishingAttribute($attributeName)) {
						$parsedAttributes .= '[@' . $attributeName . '="' . $attributeValue . '"]';
					}
				}

				$nameOfChild .= $parsedAttributes;
			}

			if (!isset($parsedNode[$nameOfChild])) {
					// We accept only first child when they are non distinguishable (i.e. they differs only by non-distinguishing attributes)
				$parsedNode[$nameOfChild] = $parsedChild;
			}
		}

		return $parsedNode;
	}

	/**
	 * Checks if given attribute belongs to the group of distinguishing attributes
	 *
	 * Distinguishing attributes in CLDR serves to distinguish multiple elements
	 * at the same level (most notably 'type').
	 *
	 * @param string $attributeName
	 * @return boolean
	 */
	protected function isDistinguishingAttribute($attributeName) {
			// Taken from SupplementalMetadata and hardcoded for now
		$distinguishingAttributes = array ('key', 'request', 'id', '_q', 'registry', 'alt', 'iso4217', 'iso3166', 'mzone', 'from', 'to', 'type');

			// These are not defined as distinguishing in CLDR but we need to preserve them for alias resolving later
		$distinguishingAttributes[] = 'source';
		$distinguishingAttributes[] = 'path';

			// These are needed for proper plurals handling
		$distinguishingAttributes[] = 'locales';
		$distinguishingAttributes[] = 'count';

			// we need this one for datetime parsing (default[@choice] nodes)
		$distinguishingAttributes[] = 'choice';

		return in_array($attributeName, $distinguishingAttributes);
	}
}

namespace TYPO3\Flow\I18n\Cldr;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A class which parses CLDR file to simple but useful array representation.
 * 
 * Parsed data is an array where keys are nodes from XML file with its attributes
 * (if any). Only distinguishing attributes are taken into account (see [1]).
 * Below are examples of parsed data structure.
 * 
 * such XML data:
 * <dates>
 *   <calendars>
 *     <calendar type="gregorian">
 *       <months />
 *     </calendar>
 *     <calendar type="buddhist">
 *       <months />
 *     </calendar>
 *   </calendars>
 * </dates>
 * 
 * will be converted to such array:
 * array(
 *   'dates' => array(
 *     'calendars' => array(
 *       'calendar[@type="gregorian"]' => array(
 *         'months' => ''
 *       ),
 *       'calendar[@type="buddhist"]' => array(
 *         'months' => ''
 *       ),
 *     )
 *   )
 * )
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class CldrParser extends CldrParser_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Cldr\CldrParser') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Cldr\CldrParser', $this);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\I18n\Cldr\CldrParser') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\I18n\Cldr\CldrParser', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\I18n\Cldr\CldrParser');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\I18n\Cldr\CldrParser', $propertyName, 'transient')) continue;
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