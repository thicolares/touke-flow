<?php
namespace TYPO3\Flow\Mvc\Routing;

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
 * Dynamic Route Part
 *
 * @api
 */
class DynamicRoutePart_Original extends \TYPO3\Flow\Mvc\Routing\AbstractRoutePart implements \TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * The split string represents the end of a Dynamic Route Part.
	 * If it is empty, Route Part will be equal to the remaining request path.
	 *
	 * @var string
	 */
	protected $splitString = '';

	/**
	 * Sets split string of the Route Part.
	 *
	 * @param string $splitString
	 * @return void
	 * @api
	 */
	public function setSplitString($splitString) {
		$this->splitString = $splitString;
	}

	/**
	 * Checks whether this Dynamic Route Part corresponds to the given $routePath.
	 *
	 * On successful match this method sets $this->value to the corresponding uriPart
	 * and shortens $routePath respectively.
	 *
	 * @param string $routePath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
	 */
	final public function match(&$routePath) {
		$this->value = NULL;
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToMatch = $this->findValueToMatch($routePath);
		$matchResult = $this->matchValue($valueToMatch);
		if ($matchResult !== TRUE) {
			return $matchResult;
		}
		$this->removeMatchingPortionFromRequestPath($routePath, $valueToMatch);

		return TRUE;
	}

	/**
	 * Returns the first part of $routePath.
	 * If a split string is set, only the first part of the value until location of the splitString is returned.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $routePath The request path to be matched
	 * @return string value to match, or an empty string if $routePath is empty or split string was not found
	 * @api
	 */
	protected function findValueToMatch($routePath) {
		if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
			return '';
		}
		$valueToMatch = $routePath;
		if ($this->splitString !== '') {
			$splitStringPosition = strpos($valueToMatch, $this->splitString);
			if ($splitStringPosition !== FALSE) {
				$valueToMatch = substr($valueToMatch, 0, $splitStringPosition);
			}
		}
		if (strpos($valueToMatch, '/') !== FALSE) {
			return '';
		}
		return $valueToMatch;
	}

	/**
	 * Checks, whether given value can be matched.
	 * In the case of default Dynamic Route Parts a value matches when it's not empty.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @api
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}
		$this->value = $value;
		return TRUE;
	}

	/**
	 * Removes matching part from $routePath.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $routePath The request path to be matched
	 * @param string $valueToMatch The matching value
	 * @return void
	 * @api
	 */
	protected function removeMatchingPortionFromRequestPath(&$routePath, $valueToMatch) {
		if ($valueToMatch !== NULL && $valueToMatch !== '') {
			$routePath = substr($routePath, strlen($valueToMatch));
		}
	}

	/**
	 * Checks whether $routeValues contains elements which correspond to this Dynamic Route Part.
	 * If a corresponding element is found in $routeValues, this element is removed from the array.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if current Route Part could be resolved, otherwise FALSE
	 */
	final public function resolve(array &$routeValues) {
		$this->value = NULL;
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		$valueToResolve = $this->findValueToResolve($routeValues);
		if (!$this->resolveValue($valueToResolve)) {
			return FALSE;
		}
		if ($this->lowerCase) {
			$this->value = strtolower($this->value);
		}
		$routeValues = \TYPO3\Flow\Utility\Arrays::unsetValueByPath($routeValues, $this->name);
		return TRUE;
	}

	/**
	 * Returns the route value of the current route part.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return string|array value to resolve.
	 * @api
	 */
	protected function findValueToResolve(array $routeValues) {
		return \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($routeValues, $this->name);
	}

	/**
	 * Checks, whether given value can be resolved and if so, sets $this->value to the resolved value.
	 * If $value is empty, this method checks whether a default value exists.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param string $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @api
	 */
	protected function resolveValue($value) {
		if ($value === NULL) {
			return FALSE;
		}
		if (is_object($value)) {
			$value = $this->persistenceManager->getIdentifierByObject($value);
			if ($value === NULL || !is_string($value)) {
				return FALSE;
			}
		}
		$this->value = (string)$value;
		if ($this->lowerCase) {
			$this->value = strtolower($this->value);
		}
		return TRUE;
	}
}
namespace TYPO3\Flow\Mvc\Routing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Dynamic Route Part
 */
class DynamicRoutePart extends DynamicRoutePart_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Mvc\Routing\DynamicRoutePart' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\DynamicRoutePart');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\DynamicRoutePart', $propertyName, 'transient')) continue;
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