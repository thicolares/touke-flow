<?php
namespace TYPO3\Fluid\Core\Parser;

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
 * Stores all information relevant for one parsing pass - that is, the root node,
 * and the current stack of open nodes (nodeStack) and a variable container used
 * for PostParseFacets.
 *
 */
class ParsingState_Original implements \TYPO3\Fluid\Core\Parser\ParsedTemplateInterface {

	/**
	 * Root node reference
	 * @var \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode
	 */
	protected $rootNode;

	/**
	 * Array of node references currently open.
	 * @var array
	 */
	protected $nodeStack = array();

	/**
	 * Variable container where ViewHelpers implementing the PostParseFacet can
	 * store things in.
	 * @var \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer
	 */
	protected $variableContainer;

	/**
	 * The layout name of the current template or NULL if the template does not contain a layout definition
	 *
	 * @var \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode
	 */
	protected $layoutNameNode;

	/**
	 * @var boolean
	 */
	protected $compilable = TRUE;

	/**
	 * Injects a variable container. ViewHelpers implementing the PostParse
	 * Facet can store information inside this variableContainer.
	 *
	 * @param \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer
	 * @return void
	 */
	public function injectVariableContainer(\TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}

	/**
	 * Set root node of this parsing state
	 *
	 * @param \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $rootNode
	 * @return void
	 */
	public function setRootNode(\TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $rootNode) {
		$this->rootNode = $rootNode;
	}

	/**
	 * Get root node of this parsing state.
	 *
	 * @return \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode The root node
	 */
	public function getRootNode() {
		return $this->rootNode;
	}

	/**
	 * Render the parsed template with rendering context
	 *
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext The rendering context to use
	 * @return string Rendered string
	 */
	public function render(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		return $this->rootNode->evaluate($renderingContext);
	}

	/**
	 * Push a node to the node stack. The node stack holds all currently open
	 * templating tags.
	 *
	 * @param \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $node Node to push to node stack
	 * @return void
	 */
	public function pushNodeToStack(\TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $node) {
		array_push($this->nodeStack, $node);
	}

	/**
	 * Get the top stack element, without removing it.
	 *
	 * @return \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode the top stack element.
	 */
	public function getNodeFromStack() {
		return $this->nodeStack[count($this->nodeStack)-1];
	}

	/**
	 * Pop the top stack element (=remove it) and return it back.
	 *
	 * @return \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode the top stack element, which was removed.
	 */
	public function popNodeFromStack() {
		return array_pop($this->nodeStack);
	}

	/**
	 * Count the size of the node stack
	 *
	 * @return integer Number of elements on the node stack (i.e. number of currently open Fluid tags)
	 */
	public function countNodeStack() {
		return count($this->nodeStack);
	}

	/**
	 * Returns a variable container which will be then passed to the postParseFacet.
	 *
	 * @return \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer The variable container or NULL if none has been set yet
	 * @todo Rename to getPostParseVariableContainer
	 */
	public function getVariableContainer() {
		return $this->variableContainer;
	}

	/**
	 * @param \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $layoutNameNode name of the layout that is defined in this template via <f:layout name="..." />
	 * @return void
	 */
	public function setLayoutNameNode(\TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode $layoutNameNode) {
		$this->layoutNameNode = $layoutNameNode;
	}

	/**
	 * @return \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode
	 */
	public function getLayoutNameNode() {
		return $this->layoutNameNode;
	}

	/**
	 * Returns TRUE if the current template has a template defined via <f:layout name="..." />
	 * @see getLayoutName()
	 *
	 * @return boolean
	 */
	public function hasLayout() {
		return $this->layoutNameNode !== NULL;
	}

	/**
	 * Returns the name of the layout that is defined within the current template via <f:layout name="..." />
	 * If no layout is defined, this returns NULL
	 * This requires the current rendering context in order to be able to evaluate the layout name
	 *
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return string
	 * @throws \TYPO3\Fluid\View\Exception
	 */
	public function getLayoutName(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		if (!$this->hasLayout()) {
			return NULL;
		}
		$layoutName = $this->layoutNameNode->evaluate($renderingContext);
		if (!empty($layoutName)) {
			return $layoutName;
		}
		throw new \TYPO3\Fluid\View\Exception('The layoutName could not be evaluated to a string', 1296805368);
	}

	/**
	 * @return boolean
	 */
	public function isCompilable() {
		return $this->compilable;
	}

	/**
	 * @param boolean $compilable
	 */
	public function setCompilable($compilable) {
		$this->compilable = $compilable;
	}

	/**
	 * @return boolean
	 */
	public function isCompiled() {
		return FALSE;
	}
}
namespace TYPO3\Fluid\Core\Parser;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Stores all information relevant for one parsing pass - that is, the root node,
 * and the current stack of open nodes (nodeStack) and a variable container used
 * for PostParseFacets.
 */
class ParsingState extends ParsingState_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Fluid\Core\Parser\ParsingState' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\Core\Parser\ParsingState');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\Core\Parser\ParsingState', $propertyName, 'transient')) continue;
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
		$this->injectVariableContainer(new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array (
)));
	}
}
#