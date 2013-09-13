<?php
namespace TYPO3\Fluid\Core\Widget;

/*
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
 * The WidgetContext stores all information a widget needs to know about the
 * environment.
 *
 * The WidgetContext can be fetched from the current request as internal argument __widgetContext,
 * and is thus available throughout the whole sub-request of the widget. It is used internally
 * by various ViewHelpers (like <f:widget.link>, <f:widget.link>, <f:widget.renderChildren>),
 * to get knowledge over the current widget's configuration.
 *
 * It is a purely internal class which should not be used outside of Fluid.
 *
 */
class WidgetContext_Original {

	/**
	 * Uniquely idenfies a Widget Instance on a certain page.
	 *
	 * @var string
	 */
	protected $widgetIdentifier;

	/**
	 * Per-User unique identifier of the widget, if it is an AJAX widget.
	 *
	 * @var integer
	 */
	protected $ajaxWidgetIdentifier;

	/**
	 * User-supplied widget configuration, available inside the widget
	 * controller as $this->widgetConfiguration, if being inside an AJAX
	 * request
	 *
	 * @var array
	 */
	protected $ajaxWidgetConfiguration;

	/**
	 * User-supplied widget configuration, available inside the widget
	 * controller as $this->widgetConfiguration, if being inside a non-AJAX
	 * request
	 *
	 * @var array
	 */
	protected $nonAjaxWidgetConfiguration;
	/**
	 * The fully qualified object name of the Controller which this widget uses.
	 *
	 * @var string
	 */
	protected $controllerObjectName;

	/**
	 * The child nodes of the Widget ViewHelper.
	 * Only available inside non-AJAX requests.
	 *
	 * @var \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode
	 * @Flow\Transient
	 */
	protected $viewHelperChildNodes; // TODO: rename to something more meaningful.

	/**
	 * The rendering context of the ViewHelperChildNodes.
	 * Only available inside non-AJAX requests.
	 *
	 * @var \TYPO3\Fluid\Core\Rendering\RenderingContextInterface
	 * @Flow\Transient
	 */
	protected $viewHelperChildNodeRenderingContext;

	/**
	 * @return string
	 */
	public function getWidgetIdentifier() {
		return $this->widgetIdentifier;
	}

	/**
	 * @param string $widgetIdentifier
	 * @return void
	 */
	public function setWidgetIdentifier($widgetIdentifier) {
		$this->widgetIdentifier = $widgetIdentifier;
	}

	/**
	 * @return integer
	 */
	public function getAjaxWidgetIdentifier() {
		return $this->ajaxWidgetIdentifier;
	}

	/**
	 * @param integer $ajaxWidgetIdentifier
	 * @return void
	 */
	public function setAjaxWidgetIdentifier($ajaxWidgetIdentifier) {
		$this->ajaxWidgetIdentifier = $ajaxWidgetIdentifier;
	}

	/**
	 * @return array
	 */
	public function getWidgetConfiguration() {
		if ($this->nonAjaxWidgetConfiguration !== NULL) {
			return $this->nonAjaxWidgetConfiguration;
		} else {
			return $this->ajaxWidgetConfiguration;
		}
	}

	/**
	 * @param array $ajaxWidgetConfiguration
	 * @return void
	 */
	public function setAjaxWidgetConfiguration($ajaxWidgetConfiguration) {
		$this->ajaxWidgetConfiguration = $ajaxWidgetConfiguration;
	}

	/**
	 * @param array $nonAjaxWidgetConfiguration
	 * @return void
	 */
	public function setNonAjaxWidgetConfiguration($nonAjaxWidgetConfiguration) {
		$this->nonAjaxWidgetConfiguration = $nonAjaxWidgetConfiguration;
	}

	/**
	 * @return string
	 */
	public function getControllerObjectName() {
		return $this->controllerObjectName;
	}

	/**
	 * @param string $controllerObjectName
	 * @return void
	 */
	public function setControllerObjectName($controllerObjectName) {
		$this->controllerObjectName = $controllerObjectName;
	}

	/**
	 * @param \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext
	 * @return void
	 */
	public function setViewHelperChildNodes(\TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes, \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext) {
		$this->viewHelperChildNodes = $viewHelperChildNodes;
		$this->viewHelperChildNodeRenderingContext = $viewHelperChildNodeRenderingContext;
	}

	/**
	 * @return \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode
	 */
	public function getViewHelperChildNodes() {
		return $this->viewHelperChildNodes;
	}

	/**
	 * @return \TYPO3\Fluid\Core\Rendering\RenderingContextInterface
	 */
	public function getViewHelperChildNodeRenderingContext() {
		return $this->viewHelperChildNodeRenderingContext;
	}

	/**
	 * Serialize everything *except* the viewHelperChildNodes, viewHelperChildNodeRenderingContext and nonAjaxWidgetConfiguration
	 *
	 * @return array
	 */
	public function __sleep() {
		return array('widgetIdentifier', 'ajaxWidgetIdentifier', 'ajaxWidgetConfiguration', 'controllerObjectName');
	}
}
namespace TYPO3\Fluid\Core\Widget;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The WidgetContext stores all information a widget needs to know about the
 * environment.
 * 
 * The WidgetContext can be fetched from the current request as internal argument __widgetContext,
 * and is thus available throughout the whole sub-request of the widget. It is used internally
 * by various ViewHelpers (like <f:widget.link>, <f:widget.link>, <f:widget.renderChildren>),
 * to get knowledge over the current widget's configuration.
 * 
 * It is a purely internal class which should not be used outside of Fluid.
 */
class WidgetContext extends WidgetContext_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


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