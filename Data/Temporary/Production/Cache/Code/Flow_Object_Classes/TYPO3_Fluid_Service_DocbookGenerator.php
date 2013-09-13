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

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 *
 */
class DocbookGenerator_Original extends \TYPO3\Fluid\Service\AbstractGenerator {

	/**
	 * Generate the XML Schema definition for a given namespace.
	 *
	 * @param string $namespace Namespace identifier to generate the XSD for, without leading Backslash.
	 * @return string XML Schema definition
	 */
	public function generateDocbook($namespace) {
		if (substr($namespace, -1) !== \TYPO3\Fluid\Fluid::NAMESPACE_SEPARATOR) {
			$namespace .= \TYPO3\Fluid\Fluid::NAMESPACE_SEPARATOR;
		}

		$classNames = $this->getClassNamesInNamespace($namespace);

		$xmlRootNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<section version="5.0" xmlns="http://docbook.org/ns/docbook"
         xml:id="fluid.usermanual.standardviewhelpers"
         xmlns:xl="http://www.w3.org/1999/xlink"
         xmlns:xi="http://www.w3.org/2001/XInclude"
         xmlns:xhtml="http://www.w3.org/1999/xhtml"
         xmlns:svg="http://www.w3.org/2000/svg"
         xmlns:ns="http://docbook.org/ns/docbook"
         xmlns:mathml="http://www.w3.org/1998/Math/MathML">
    <title>Standard View Helper Library</title>

    <para>Should be autogenerated from the tags.</para>
</section>');

		foreach ($classNames as $className) {
			$this->generateXmlForClassName($className, $namespace, $xmlRootNode);
		}

		return $xmlRootNode->asXML();
	}

	/**
	 * Generate the XML Schema for a given class name.
	 *
	 * @param string $className Class name to generate the schema for.
	 * @param string $namespace Namespace prefix. Used to split off the first parts of the class name.
	 * @param \SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
	 * @return void
	 */
	protected function generateXmlForClassName($className, $namespace, \SimpleXMLElement $xmlRootNode) {
		$reflectionClass = new \TYPO3\Flow\Reflection\ClassReflection($className);
		if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
			return;
		}

		$tagName = $this->getTagNameForClass($className, $namespace);

		$docbookSection = $xmlRootNode->addChild('section');

		$docbookSection->addChild('title', $tagName);
		$this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
		$this->addDocumentation($this->docCommentParser->getDescription(), $docbookSection);

		$argumentsSection = $docbookSection->addChild('section');
		$argumentsSection->addChild('title', 'Arguments');
		$this->addArguments($className, $argumentsSection);

		return $docbookSection;
	}

	/**
	 * Add attribute descriptions to a given tag.
	 * Initializes the view helper and its arguments, and then reads out the list of arguments.
	 *
	 * @param string $className Class name where to add the attribute descriptions
	 * @param \SimpleXMLElement $docbookSection DocBook section to add the attributes to.
	 * @return void
	 */
	protected function addArguments($className, \SimpleXMLElement $docbookSection) {
		$viewHelper = $this->instanciateViewHelper($className);
		$argumentDefinitions = $viewHelper->prepareArguments();

		if (count($argumentDefinitions) === 0) {
			$docbookSection->addChild('para', 'No arguments defined.');
			return;
		}
		$argumentsTable = $docbookSection->addChild('table');
		$argumentsTable->addChild('title', 'Arguments');
		$tgroup = $argumentsTable->addChild('tgroup');
		$tgroup['cols'] = 4;
		$this->addArgumentTableRow($tgroup->addChild('thead'), 'Name', 'Type', 'Required', 'Description', 'Default');

		$tbody = $tgroup->addChild('tbody');

		foreach ($argumentDefinitions as $argumentDefinition) {
			$this->addArgumentTableRow($tbody, $argumentDefinition->getName(), $argumentDefinition->getType(), ($argumentDefinition->isRequired()?'yes':'no'), $argumentDefinition->getDescription(), $argumentDefinition->getDefaultValue());
		}
	}

	/**
	 * Instantiate a view helper.
	 *
	 * @param string $className
	 * @return object
	 */
	protected function instanciateViewHelper($className) {
		return $this->objectManager->get($className);
	}

	/**
	 * @param \SimpleXMLElement $parent
	 * @param string $name
	 * @param string $type
	 * @param boolean $required
	 * @param string $description
	 * @param string $default
	 * @return void
	 */
	private function addArgumentTableRow(\SimpleXMLElement $parent, $name, $type, $required, $description, $default) {
		$row = $parent->addChild('row');

		$row->addChild('entry', $name);
		$row->addChild('entry', $type);
		$row->addChild('entry', $required);
		$row->addChild('entry', $description);
		$row->addChild('entry', (string)$default);
	}

	/**
	 * Add documentation XSD to a given XML node
	 *
	 * As Eclipse renders newlines only on new <xsd:documentation> tags, we wrap every line in a new
	 * <xsd:documentation> tag.
	 * Furthermore, eclipse strips out tags - the only way to prevent this is to have every line wrapped in a
	 * CDATA block AND to replace the < and > with their XML entities. (This is IMHO not XML conformant).
	 *
	 * @param string $documentation Documentation string to add.
	 * @param \SimpleXMLElement $docbookSection Node to add the documentation to
	 * @return void
	 */
	protected function addDocumentation($documentation, \SimpleXMLElement $docbookSection) {
		$splitRegex = '/^\s*(=[^=]+=)$/m';
		$regex = '/^\s*(=([^=]+)=)$/m';

		$matches = preg_split($splitRegex, $documentation, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );

		$currentSection = $docbookSection;
		foreach ($matches as $singleMatch) {
			if (preg_match($regex, $singleMatch, $tmp)) {
				$currentSection = $docbookSection->addChild('section');
				$currentSection->addChild('title', trim($tmp[2]));
			} else {
				$this->addText(trim($singleMatch), $currentSection);
			}
		}
	}

	/**
	 * @param string $text
	 * @param \SimpleXMLElement $parentElement
	 */
	protected function addText($text, \SimpleXMLElement $parentElement) {
		$splitRegex = '/
		(<code(?:.*?)>
			(?:.*?)
		<\/code>)/xs';

		$regex = '/
		<code(.*?)>
			(.*?)
		<\/code>/xs';
		$matches = preg_split($splitRegex, $text, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );
		foreach ($matches as $singleMatch) {

			if (preg_match($regex, $singleMatch, $tmp)) {
				preg_match('/title="([^"]+)"/', $tmp[1], $titleMatch);

				$example = $parentElement->addChild('example');
				if (count($titleMatch)) {
					$example->addChild('title', trim($titleMatch[1]));
				} else {
					$example->addChild('title', 'Example');
				}
				$this->addChildWithCData($example, 'programlisting', trim($tmp[2]));
			} else {
				$textParts = explode("\n", $singleMatch);
				foreach ($textParts as $text) {
					if (trim($text) === '') continue;
					$this->addChildWithCData($parentElement, 'para', trim($text));
				}
			}
		}
	}
}
namespace TYPO3\Fluid\Service;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 */
class DocbookGenerator extends DocbookGenerator_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Fluid\Service\DocbookGenerator' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\Service\DocbookGenerator');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\Service\DocbookGenerator', $propertyName, 'transient')) continue;
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