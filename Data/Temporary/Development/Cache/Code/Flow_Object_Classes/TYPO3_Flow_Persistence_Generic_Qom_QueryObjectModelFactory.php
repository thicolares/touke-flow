<?php
namespace TYPO3\Flow\Persistence\Generic\Qom;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * The Query Object Model Factory
 *
 * @api
 */
class QueryObjectModelFactory_Original {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object factory
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1 the first constraint; non-null
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2 the second constraint; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalAnd the And constraint; non-null
	 * @api
	 */
	public function _and(\TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1, \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\LogicalAnd($constraint1, $constraint2);
	}

	/**
	 * Performs a logical disjunction of two other constraints.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1 the first constraint; non-null
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2 the second constraint; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalOr the Or constraint; non-null
	 * @api
	 */
	public function _or(\TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1, \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\LogicalOr($constraint1, $constraint2);
	}

	/**
	 * Performs a logical negation of another constraint.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint the constraint to be negated; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalNot the Not constraint; non-null
	 * @api
	 */
	public function not(\TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\LogicalNot($constraint);
	}

	/**
	 * Filters tuples based on the outcome of a binary operation.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param mixed $operand2 the second operand; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison the constraint; non-null
	 * @api
	 */
	public function comparison(\TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand1, $operator, $operand2 = NULL) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\Comparison($operand1, $operator, $operand2);
	}

	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\PropertyValue the operand; non-null
	 * @api
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return new \TYPO3\Flow\Persistence\Generic\Qom\PropertyValue($propertyName, $selectorName);
	}

	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand the operand whose value is converted to a lower-case string; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LowerCase the operand; non-null
	 * @api
	 */
	public function lowerCase(\TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\LowerCase($operand);
	}

	/**
	 * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand the operand whose value is converted to a upper-case string; non-null
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\UpperCase the operand; non-null
	 * @api
	 */
	public function upperCase(\TYPO3\Flow\Persistence\Generic\Qom\DynamicOperand $operand) {
		return new \TYPO3\Flow\Persistence\Generic\Qom\UpperCase($operand);
	}

}
namespace TYPO3\Flow\Persistence\Generic\Qom;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The Query Object Model Factory
 */
class QueryObjectModelFactory extends QueryObjectModelFactory_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory', $propertyName, 'transient')) continue;
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
	}
}
#