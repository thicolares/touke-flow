<?php
namespace TYPO3\Fluid\Service;

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
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 */
class XsdGenerator_Original extends \TYPO3\Fluid\Service\AbstractGenerator {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * Generate the XML Schema definition for a given namespace.
	 * It will generate an XSD file for all view helpers in this namespace.
	 *
	 * @param string $viewHelperNamespace Namespace identifier to generate the XSD for, without leading Backslash.
	 * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
	 * @return string XML Schema definition
	 * @throws Exception
	 */
	public function generateXsd($viewHelperNamespace, $xsdNamespace) {
		if (substr($viewHelperNamespace, -1) !== '\\') {
			$viewHelperNamespace .= '\\';
		}

		$classNames = $this->getClassNamesInNamespace($viewHelperNamespace);
		if (count($classNames) === 0) {
			throw new \TYPO3\Fluid\Service\Exception(sprintf('No ViewHelpers found in namespace "%s"', $viewHelperNamespace), 1330029328);
		}

		$xmlRootNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="' . $xsdNamespace . '"></xsd:schema>');

		foreach ($classNames as $className) {
			$this->generateXmlForClassName($className, $viewHelperNamespace, $xmlRootNode);
		}

		return $xmlRootNode->asXML();
	}

	/**
	 * Generate the XML Schema for a given class name.
	 *
	 * @param string $className Class name to generate the schema for.
	 * @param string $viewHelperNamespace Namespace prefix. Used to split off the first parts of the class name.
	 * @param \SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
	 * @return void
	 */
	protected function generateXmlForClassName($className, $viewHelperNamespace, \SimpleXMLElement $xmlRootNode) {
		$reflectionClass = new \TYPO3\Flow\Reflection\ClassReflection($className);
		if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
			return;
		}

		$tagName = $this->getTagNameForClass($className, $viewHelperNamespace);

		$xsdElement = $xmlRootNode->addChild('xsd:element');
		$xsdElement['name'] = $tagName;
		$this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
		$this->addDocumentation($this->docCommentParser->getDescription(), $xsdElement);

		$xsdComplexType = $xsdElement->addChild('xsd:complexType');
		$xsdComplexType['mixed'] = 'true';
		$xsdSequence = $xsdComplexType->addChild('xsd:sequence');
		$xsdAny = $xsdSequence->addChild('xsd:any');
		$xsdAny['minOccurs'] = '0';
		$xsdAny['maxOccurs'] = 'unbounded';

		$this->addAttributes($className, $xsdComplexType);
	}

	/**
	 * Add attribute descriptions to a given tag.
	 * Initializes the view helper and its arguments, and then reads out the list of arguments.
	 *
	 * @param string $className Class name where to add the attribute descriptions
	 * @param \SimpleXMLElement $xsdElement XML element to add the attributes to.
	 * @return void
	 */
	protected function addAttributes($className, \SimpleXMLElement $xsdElement) {
		$viewHelper = $this->objectManager->get($className);
		$argumentDefinitions = $viewHelper->prepareArguments();

		foreach ($argumentDefinitions as $argumentDefinition) {
			$xsdAttribute = $xsdElement->addChild('xsd:attribute');
			$xsdAttribute['type'] = 'xsd:string';
			$xsdAttribute['name'] = $argumentDefinition->getName();
			$this->addDocumentation($argumentDefinition->getDescription(), $xsdAttribute);
			if ($argumentDefinition->isRequired()) {
				$xsdAttribute['use'] = 'required';
			}
		}
	}

	/**
	 * Add documentation XSD to a given XML node
	 *
	 * @param string $documentation Documentation string to add.
	 * @param \SimpleXMLElement $xsdParentNode Node to add the documentation to
	 * @return void
	 */
	protected function addDocumentation($documentation, \SimpleXMLElement $xsdParentNode) {
		$xsdAnnotation = $xsdParentNode->addChild('xsd:annotation');
		$this->addChildWithCData($xsdAnnotation, 'xsd:documentation', $documentation);
	}
}
namespace TYPO3\Fluid\Service;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 */
class XsdGenerator extends XsdGenerator_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Fluid\Service\XsdGenerator' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\Service\XsdGenerator');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\Service\XsdGenerator', $propertyName, 'transient')) continue;
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
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
		$docCommentParser_reference = &$this->docCommentParser;
		$this->docCommentParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\DocCommentParser');
		if ($this->docCommentParser === NULL) {
			$this->docCommentParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('e2b96b26ad09c71d8f999d685cc1924a', $docCommentParser_reference);
			if ($this->docCommentParser === NULL) {
				$this->docCommentParser = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('e2b96b26ad09c71d8f999d685cc1924a',  $docCommentParser_reference, 'TYPO3\Flow\Reflection\DocCommentParser', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\DocCommentParser'); });
			}
		}
		$reflectionService_reference = &$this->reflectionService;
		$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\ReflectionService');
		if ($this->reflectionService === NULL) {
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('921ad637f16d2059757a908fceaf7076', $reflectionService_reference);
			if ($this->reflectionService === NULL) {
				$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('921ad637f16d2059757a908fceaf7076',  $reflectionService_reference, 'TYPO3\Flow\Reflection\ReflectionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'); });
			}
		}
	}
}
#