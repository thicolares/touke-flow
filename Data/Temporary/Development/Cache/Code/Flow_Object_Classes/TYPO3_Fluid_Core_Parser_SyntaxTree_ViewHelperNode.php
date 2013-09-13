<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

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
use TYPO3\Flow\Object\DependencyInjection\DependencyProxy;

/**
 * Node which will call a ViewHelper associated with this node.
 *
 */
class ViewHelperNode_Original extends \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode {

	/**
	 * Class name of view helper
	 * @var string
	 */
	protected $viewHelperClassName;

	/**
	 * Arguments of view helper - References to RootNodes.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * The ViewHelper associated with this node
	 * @var \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
	 */
	protected $uninitializedViewHelper = NULL;

	/**
	 * A mapping RenderingContext -> ViewHelper to only re-initialize ViewHelpers
	 * when a context change occurs.
	 * @var \SplObjectStorage
	 */
	protected $viewHelpersByContext = NULL;



	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper The view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 */
	public function __construct(\TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper, array $arguments) {
		$this->uninitializedViewHelper = $viewHelper;
		$this->viewHelpersByContext = new \SplObjectStorage();
		$this->arguments = $arguments;
		$this->viewHelperClassName = ($this->uninitializedViewHelper instanceof DependencyProxy) ? $this->uninitializedViewHelper->_getClassName() : get_class($this->uninitializedViewHelper);
	}

	/**
	 * Returns the attached (but still uninitialized) ViewHelper for this ViewHelperNode.
	 * We need this method because sometimes Interceptors need to ask some information from the ViewHelper.
	 *
	 * @return \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper the attached ViewHelper, if it is initialized
	 */
	public function getUninitializedViewHelper() {
		return $this->uninitializedViewHelper;
	}

	/**
	 * Get class name of view helper
	 *
	 * @return string Class Name of associated view helper
	 */
	public function getViewHelperClassName() {
		return $this->viewHelperClassName;
	}

	/**
	 * INTERNAL - only needed for compiling templates
	 *
	 * @return array
	 * @Flow\Internal
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Call the view helper associated with this object.
	 *
	 * First, it evaluates the arguments of the view helper.
	 *
	 * If the view helper implements \TYPO3\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface,
	 * it calls setChildNodes(array childNodes) on the view helper.
	 *
	 * Afterwards, checks that the view helper did not leave a variable lying around.
	 *
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return object evaluated node after the view helper has been called.
	 */
	public function evaluate(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		if ($this->viewHelpersByContext->contains($renderingContext)) {
			$viewHelper = $this->viewHelpersByContext[$renderingContext];
			$viewHelper->resetState();
		} else {
			$viewHelper = clone $this->uninitializedViewHelper;
			$this->viewHelpersByContext->attach($renderingContext, $viewHelper);
		}

		$evaluatedArguments = array();
		if (count($viewHelper->prepareArguments())) {
 			foreach ($viewHelper->prepareArguments() as $argumentName => $argumentDefinition) {
				if (isset($this->arguments[$argumentName])) {
					$argumentValue = $this->arguments[$argumentName];
					$evaluatedArguments[$argumentName] = $argumentValue->evaluate($renderingContext);
				} else {
					$evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
				}
			}
		}

		$viewHelper->setArguments($evaluatedArguments);
		$viewHelper->setViewHelperNode($this);
		$viewHelper->setRenderingContext($renderingContext);

		if ($viewHelper instanceof \TYPO3\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface) {
			$viewHelper->setChildNodes($this->childNodes);
		}

		$output = $viewHelper->initializeArgumentsAndRender();

		return $output;
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array('viewHelperClassName', 'arguments', 'childNodes');
	}
}

namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Node which will call a ViewHelper associated with this node.
 */
class ViewHelperNode extends ViewHelperNode_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper The view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper');
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $viewHelper in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $arguments in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
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