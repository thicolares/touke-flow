<?php
namespace TYPO3\Flow\Persistence\Generic;

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
 * The Query class used to run queries like
 * $query->matching($query->equals('foo', 'bar'))->setLimit(10)->execute();
 *
 * @api
 */
class Query_Original implements \TYPO3\Flow\Persistence\QueryInterface {

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var \TYPO3\Flow\Reflection\ClassSchema
	 */
	protected $classSchema;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint;

	/**
	 * The property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @var array
	 */
	protected $orderings = array();

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * Constructs a query object working on the given type
	 *
	 * @param string $type
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 */
	public function __construct($type, \TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->type = $type;
		$this->classSchema = $reflectionService->getClassSchema($type);
	}

	/**
	 * Injects the Flow object factory
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $qomFactory
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $qomFactory) {
		$this->objectManager = $qomFactory;
	}

	/**
	 * Injects the Flow QOM factory
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory
	 * @return void
	 */
	public function injectQomFactory(\TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Executes the query and returns the result
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 * @api
	 */
	public function execute() {
		return new \TYPO3\Flow\Persistence\Generic\QueryResult($this);
	}

	/**
	 * Returns the query result count
	 *
	 * @return integer The query result count
	 * @api
	 */
	public function count() {
		$result = new \TYPO3\Flow\Persistence\Generic\QueryResult($this);
		return $result->count();
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
		return $this;
	}

	/**
	 * Returns the property names to order the result by, like this:
	 * array(
	 *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 * @api
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setLimit($limit) {
		if ($limit < 1 || !is_int($limit)) {
			throw new \InvalidArgumentException('setLimit() accepts only integers greater 0.', 1263387249);
		}
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @return integer
	 * @api
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the start offset of the result set to $offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setOffset($offset) {
		if ($offset < 1 || !is_int($offset)) {
			throw new \InvalidArgumentException('setOffset() accepts only integers greater 0.', 1263387252);
		}
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 * @api
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint the constraint, or null if none
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints. The method
	 * takes one or more contraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalAnd
	 * @throws \TYPO3\Flow\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}

		if ($resultingConstraint === NULL) {
			throw new \TYPO3\Flow\Persistence\Generic\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056288);
		}

		foreach ($constraints as $constraint) {
			$resultingConstraint = $this->qomFactory->_and($resultingConstraint, $constraint);
		}
		return $resultingConstraint;
	}

	/**
	 * Performs a logical disjunction of the two given constraints. The method
	 * takes one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param object $constraint1 The first of multiple constraints or an array of constraints.
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalOr
	 * @throws \TYPO3\Flow\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}

		if ($resultingConstraint === NULL) {
			throw new \TYPO3\Flow\Persistence\Generic\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056289);
		}

		foreach ($constraints as $constraint) {
			$resultingConstraint = $this->qomFactory->_or($resultingConstraint, $constraint);
		}
		return $resultingConstraint;
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\LogicalNot
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->qomFactory->not($constraint);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query.
	 *
	 * It matches if the $operand equals the value of the property named
	 * $propertyName. If $operand is NULL a strict check for NULL is done. For
	 * strings the comparison can be done with or without case-sensitivity.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
	 * @return object
	 * @todo Decide what to do about equality on multi-valued properties
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if ($operand === NULL) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_IS_NULL
			);
		} elseif (is_object($operand) || $caseSensitive) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_EQUAL_TO,
				$operand
			);
		} else {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->lowerCase(
					$this->qomFactory->propertyValue($propertyName, '_entity')
				),
				\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_EQUAL_TO,
				strtolower($operand)
			);
		}

		return $comparison;
	}

	/**
	 * Returns a like criterion used for matching objects against a query.
	 * Matches if the property named $propertyName is like the $operand, using
	 * standard SQL wildcards.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param string $operand The value to compare with
	 * @param boolean $caseSensitive Whether the matching should be done case-sensitive
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a non-string property
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		if (!is_string($operand)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Operand must be a string, was ' . gettype($operand), 1276781107);
		}
		if ($caseSensitive) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_LIKE,
				$operand
			);
		} else {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->lowerCase(
					$this->qomFactory->propertyValue($propertyName, '_entity')
				),
				\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_LIKE,
				strtolower($operand)
			);
		}

		return $comparison;
	}

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * If NULL is given as $operand, there will never be a match!
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function contains($propertyName, $operand) {
		if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276781026);
		}
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_CONTAINS,
			$operand
		);
	}

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to check
	 * @return boolean
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName) {
		if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276853547);
		}
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_IS_EMPTY
		);
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with single-valued operand
	 * @api
	 */
	public function in($propertyName, $operand) {
		if (!is_array($operand) && (!$operand instanceof \ArrayAccess) && (!$operand instanceof \Traversable)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('The "in" constraint must be given a multi-valued operand (array, ArrayAccess, Traversable).', 1264678095);
		}
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued.', 1276777034);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_IN,
			$operand
		);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784963);
		}
		if (!($operand instanceof \DateTime) && !\TYPO3\Flow\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784964);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_LESS_THAN,
			$operand
		);
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784943);
		}
		if (!($operand instanceof \DateTime) && !\TYPO3\Flow\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784944);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$operand
		);
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774885);
		}
		if (!($operand instanceof \DateTime) && !\TYPO3\Flow\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774886);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_GREATER_THAN,
			$operand
		);
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Comparison
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774883);
		}
		if (!($operand instanceof \DateTime) && !\TYPO3\Flow\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \TYPO3\Flow\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774884);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\TYPO3\Flow\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$operand
		);
	}

}
namespace TYPO3\Flow\Persistence\Generic;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The Query class used to run queries like
 * $query->matching($query->equals('foo', 'bar'))->setLimit(10)->execute();
 */
class Query extends Query_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 * @param string $type
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 */
	public function __construct() {
		$arguments = func_get_args();

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(1, $arguments)) $arguments[1] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $type in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $reflectionService in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Persistence\Generic\Query' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 protected function Flow_Aop_Proxy_buildMethodsAndAdvicesArray() {
		if (method_exists(get_parent_class($this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;
		$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
			'execute' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', 'rewriteQomQuery', $objectManager, NULL),
				),
			),
			'count' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', 'rewriteQomQuery', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

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
		$result = NULL;
		if (method_exists(get_parent_class($this), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies() {
		if (!isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices) || empty($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices)) {
			$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
			if (is_callable('parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')) parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		}	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies() {
		if (!$this instanceof \Doctrine\ORM\Proxy\Proxy || isset($this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies)) {
			return;
		}
		$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies = TRUE;
		if (is_callable(array($this, 'Flow_Proxy_injectProperties'))) {
			$this->Flow_Proxy_injectProperties();
		}	}

	/**
	 * Autogenerated Proxy Method
	 */
	 private function Flow_Aop_Proxy_getAdviceChains($methodName) {
		$adviceChains = array();
		if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
			$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
		} else {
			if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
				$groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
				if (isset($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice'])) {
					$this->Flow_Aop_Proxy_groupedAdviceChains[$methodName]['TYPO3\Flow\Aop\Advice\AroundAdvice'] = new \TYPO3\Flow\Aop\Advice\AdviceChain($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice']);
					$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
				}
			}
		}
		return $adviceChains;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if (__CLASS__ !== $joinPoint->getClassName()) return parent::Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array('self', $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}
	}

	/**
	 * Autogenerated Proxy Method
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	 public function execute() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['execute'])) {
		$result = parent::execute();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['execute'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('execute');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Generic\Query', 'execute', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['execute']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['execute']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return integer The query result count
	 */
	 public function count() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['count'])) {
		$result = parent::count();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['count'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('count');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Generic\Query', 'count', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['count']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['count']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Persistence\Generic\Query');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Persistence\Generic\Query', $propertyName, 'transient')) continue;
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
		$this->injectQomFactory(new \TYPO3\Flow\Persistence\Generic\Qom\QueryObjectModelFactory());
	}
}
#