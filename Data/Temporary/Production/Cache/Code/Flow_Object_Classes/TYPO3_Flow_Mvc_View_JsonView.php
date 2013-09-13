<?php
namespace TYPO3\Flow\Mvc\View;

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
 * A JSON view
 *
 * @api
 */
class JsonView_Original extends \TYPO3\Flow\Mvc\View\AbstractView {

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Only variables whose name is contained in this array will be rendered
	 *
	 * @var array
	 */
	protected $variablesToRender = array('value');

	/**
	 * The rendering configuration for this JSON view which
	 * determines which properties of each variable to render.
	 *
	 * The configuration array must have the following structure:
	 *
	 * Example 1:
	 *
	 * array(
	 *		'variable1' => array(
	 *			'_only' => array('property1', 'property2', ...)
	 *		),
	 *		'variable2' => array(
	 *	 		'_exclude' => array('property3', 'property4, ...)
	 *		),
	 *		'variable3' => array(
	 *			'_exclude' => array('secretTitle'),
	 *			'_descend' => array(
	 *				'customer' => array(
	 *					'_only' => array('firstName', 'lastName')
	 *				)
	 *			)
	 *		),
	 *		'somearrayvalue' => array(
	 *			'_descendAll' => array(
	 *				'_only' => array('property1')
	 *			)
	 *		)
	 * )
	 *
	 * Of variable1 only property1 and property2 will be included.
	 * Of variable2 all properties except property3 and property4
	 * are used.
	 * Of variable3 all properties except secretTitle are included.
	 *
	 * If a property value is an array or object, it is not included
	 * by default. If, however, such a property is listed in a "_descend"
	 * section, the renderer will descend into this sub structure and
	 * include all its properties (of the next level).
	 *
	 * The configuration of each property in "_descend" has the same syntax
	 * like at the top level. Therefore - theoretically - infinitely nested
	 * structures can be configured.
	 *
	 * To export indexed arrays the "_descendAll" section can be used to
	 * include all array keys for the output. The configuration inside a
	 * "_descendAll" will be applied to each array element.
	 *
	 *
	 * Example 2: exposing object identifier
	 *
	 * array(
	 *		'variableFoo' => array(
	 *			'_exclude' => array('secretTitle'),
	 *			'_descend' => array(
	 *				'customer' => array(    // consider 'customer' being a persisted entity
	 *					'_only' => array('firstName'),
	 * 					'_exposeObjectIdentifier' => TRUE,
	 * 					'_exposedObjectIdentifierKey' => 'guid'
	 *				)
	 *			)
	 *		),
	 *
	 * Note for entity objects you are able to expose the object's identifier
	 * also, just add an "_exposeObjectIdentifier" directive set to TRUE and
	 * an additional property '__identity' will appear keeping the persistence
	 * identifier. Renaming that property name instead of '__identity' is also
	 * possible with the directive "_exposedObjectIdentifierKey".
	 * Example 2 above would output (summarized):
	 * {"customer":{"firstName":"John","guid":"892693e4-b570-46fe-af71-1ad32918fb64"}}
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * Specifies which variables this JsonView should render
	 * By default only the variable 'value' will be rendered
	 *
	 * @param array $variablesToRender
	 * @return void
	 * @api
	 */
	public function setVariablesToRender(array $variablesToRender) {
		$this->variablesToRender = $variablesToRender;
	}

	/**
	 * @param array $configuration The rendering configuration for this JSON view
	 * @return void
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Transforms the value view variable to a serializable
	 * array represantion using a YAML view configuration and JSON encodes
	 * the result.
	 *
	 * @return string The JSON encoded variables
	 * @api
	 */
	public function render() {
		$this->controllerContext->getResponse()->setHeader('Content-Type', 'application/json');
		$propertiesToRender = $this->renderArray();
		return json_encode($propertiesToRender);
	}

	/**
	 * Loads the configuration and transforms the value to a serializable
	 * array.
	 *
	 * @return array An array containing the values, ready to be JSON encoded
	 * @api
	 */
	protected function renderArray() {
		if (count($this->variablesToRender) === 1) {
			$variableName = current($this->variablesToRender);
			$valueToRender = isset($this->variables[$variableName]) ? $this->variables[$variableName] : NULL;
			$configuration = isset($this->configuration[$variableName]) ? $this->configuration[$variableName] : array();
		} else {
			$valueToRender = array();
			foreach ($this->variablesToRender as $variableName) {
				$valueToRender[$variableName] = isset($this->variables[$variableName]) ? $this->variables[$variableName] : NULL;
			}
			$configuration = $this->configuration;
		}
		return $this->transformValue($valueToRender, $configuration);
	}

	/**
	 * Transforms a value depending on type recursively using the
	 * supplied configuration.
	 *
	 * @param mixed $value The value to transform
	 * @param array $configuration Configuration for transforming the value
	 * @return array The transformed value
	 */
	protected function transformValue($value, array $configuration) {
		if (is_array($value) || $value instanceof \ArrayAccess) {
			$array = array();
			foreach ($value as $key => $element) {
				if (isset($configuration['_descendAll']) && is_array($configuration['_descendAll'])) {
					$array[$key] = $this->transformValue($element, $configuration['_descendAll']);
				} else {
					if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($key, $configuration['_only'])) {
						continue;
					}
					if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($key, $configuration['_exclude'])) {
						continue;
					}
					$array[$key] = $this->transformValue($element, isset($configuration[$key]) ? $configuration[$key] : array());
				}
			}
			return $array;
		} elseif (is_object($value)) {
			return $this->transformObject($value, $configuration);
		} else {
			return $value;
		}
	}

	/**
	 * Traverses the given object structure in order to transform it into an
	 * array structure.
	 *
	 * @param object $object Object to traverse
	 * @param array $configuration Configuration for transforming the given object or NULL
	 * @return array Object structure as an array
	 */
	protected function transformObject($object, array $configuration) {
		if ($object instanceof \DateTime) {
			return $object->format(\DateTime::ISO8601);
		} else {
			$propertyNames = \TYPO3\Flow\Reflection\ObjectAccess::getGettablePropertyNames($object);

			$propertiesToRender = array();
			foreach ($propertyNames as $propertyName) {
				if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($propertyName, $configuration['_only'])) continue;
				if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($propertyName, $configuration['_exclude'])) continue;

				$propertyValue = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName);

				if (!is_array($propertyValue) && !is_object($propertyValue)) {
					$propertiesToRender[$propertyName] = $propertyValue;
				} elseif (isset($configuration['_descend']) && array_key_exists($propertyName, $configuration['_descend'])) {
					$propertiesToRender[$propertyName] = $this->transformValue($propertyValue, $configuration['_descend'][$propertyName]);
				}
			}
			if (isset($configuration['_exposeObjectIdentifier']) && $configuration['_exposeObjectIdentifier'] === TRUE) {
				if (isset($configuration['_exposedObjectIdentifierKey']) && strlen($configuration['_exposedObjectIdentifierKey']) > 0) {
					$identityKey = $configuration['_exposedObjectIdentifierKey'];
				} else {
					$identityKey = '__identity';
				}
				$propertiesToRender[$identityKey] = $this->persistenceManager->getIdentifierByObject($object);
			}
			return $propertiesToRender;
		}
	}
}

namespace TYPO3\Flow\Mvc\View;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A JSON view
 */
class JsonView extends JsonView_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Mvc\View\JsonView' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\View\JsonView');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\View\JsonView', $propertyName, 'transient')) continue;
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
		$persistenceManager_reference = &$this->persistenceManager;
		$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		if ($this->persistenceManager === NULL) {
			$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('f1bc82ad47156d95485678e33f27c110', $persistenceManager_reference);
			if ($this->persistenceManager === NULL) {
				$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('f1bc82ad47156d95485678e33f27c110',  $persistenceManager_reference, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'); });
			}
		}
	}
}
#