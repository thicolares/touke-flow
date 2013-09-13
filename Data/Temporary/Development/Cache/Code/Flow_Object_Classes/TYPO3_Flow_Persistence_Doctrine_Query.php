<?php
namespace TYPO3\Flow\Persistence\Doctrine;

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
 * A Query class for Doctrine 2
 *
 * @api
 */
class Query_Original implements \TYPO3\Flow\Persistence\QueryInterface {

	/**
	 * @var string
	 */
	protected $entityClassName;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \Doctrine\ORM\QueryBuilder
	 */
	protected $queryBuilder;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;

	/**
	 * @var mixed
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $offset;

	/**
	 * @var integer
	 */
	protected $parameterIndex = 1;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var array
	 */
	protected $joins;

	/**
	 * @param string $entityClassName
	 */
	public function __construct($entityClassName) {
		$this->entityClassName = $entityClassName;
	}

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
	 * @return void
	 */
	public function injectEntityManager(\Doctrine\Common\Persistence\ObjectManager $entityManager) {
		$this->entityManager = $entityManager;
		$this->queryBuilder = $entityManager->createQueryBuilder()->select('e')->from($this->entityClassName, 'e');
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType() {
		return $this->entityClassName;
	}

	/**
	 * Executes the query and returns the result.
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 * @api
	 */
	public function execute() {
		return new \TYPO3\Flow\Persistence\Doctrine\QueryResult($this);
	}

	/**
	 * Gets the results of this query as array.
	 *
	 * Really executes the query on the database.
	 * This should only ever be executed from the QueryResult class.
	 *
	 * @return array result set
	 * @throws \TYPO3\Flow\Persistence\Doctrine\DatabaseConnectionException
	 */
	public function getResult() {
		try {
			return $this->queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\ORMException $ormException) {
			$this->systemLogger->logException($ormException);
			return array();
		} catch (\PDOException $pdoException) {
			throw new DatabaseConnectionException($pdoException->getMessage(), $pdoException->getCode());
		}
	}

	/**
	 * Returns the query result count
	 *
	 * @return integer The query result count
	 * @throws \TYPO3\Flow\Persistence\Doctrine\DatabaseConnectionException
	 * @api
	 */
	public function count() {
		try {
			$originalQuery = $this->queryBuilder->getQuery();
			$dqlQuery = clone $originalQuery;
			$dqlQuery->setParameters($originalQuery->getParameters());
			$dqlQuery->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('TYPO3\Flow\Persistence\Doctrine\CountWalker'));
			$offset = $dqlQuery->getFirstResult();
			$limit = $dqlQuery->getMaxResults();
			if ($offset !== NULL) {
				$dqlQuery->setFirstResult(NULL);
			}
			$numberOfResults = (int)$dqlQuery->getSingleScalarResult();
			if ($offset !== NULL) {
				$numberOfResults = max(0, $numberOfResults - $offset);
			}
			if ($limit !== NULL) {
				$numberOfResults = min($numberOfResults, $limit);
			}
			return $numberOfResults;
		} catch (\Doctrine\ORM\ORMException $ormException) {
			$this->systemLogger->logException($ormException);
			return 0;
		} catch (\PDOException $pdoException) {
			throw new DatabaseConnectionException($pdoException->getMessage(), $pdoException->getCode());
		}
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
		$this->queryBuilder->resetDQLPart('orderBy');
		foreach ($this->orderings AS $propertyName => $order) {
			$this->queryBuilder->addOrderBy($this->getPropertyNameWithAlias($propertyName), $order);
		}
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
	 * @api
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
		$this->queryBuilder->setMaxResults($limit);
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
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
		$this->queryBuilder->setFirstResult($offset);
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
	 * @param object $constraint Some constraint, depending on the backend
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		$this->queryBuilder->where($constraint);
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
	 * takes one or more constraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}
		return call_user_func_array(array($this->queryBuilder->expr(), 'andX'), $constraints);
	}

	/**
	 * Performs a logical disjunction of the two given constraints. The method
	 * takes one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}
		return call_user_func_array(array($this->queryBuilder->expr(), 'orX'), $constraints);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->queryBuilder->expr()->not($constraint);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query.
	 *
	 * It matches if the $operand equals the value of the property named
	 * $propertyName. If $operand is NULL a strict check for NULL is done. For
	 * strings the comparison can be done with or without case-sensitivity.
	 *
	 * Note: case-sensitivity is only possible if the database supports it. E.g.
	 * if you are using MySQL with a case-insensitive collation you will not be able
	 * to test for case-sensitive equality (the other way around works, because we
	 * compare lowercased values).
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
	 * @return object
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		$aliasedPropertyName = $this->getPropertyNameWithAlias($propertyName);
		if ($operand === NULL) {
			return $this->queryBuilder->expr()->isNull($aliasedPropertyName);
		}

		if ($caseSensitive === TRUE) {
			return $this->queryBuilder->expr()->eq($aliasedPropertyName, $this->getParamNeedle($operand));
		}

		return $this->queryBuilder->expr()->eq($this->queryBuilder->expr()->lower($aliasedPropertyName), $this->getParamNeedle(strtolower($operand)));
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
	 * @todo implement case-sensitivity switch
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		return $this->queryBuilder->expr()->like($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * If NULL is given as $operand, there will never be a match!
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function contains($propertyName, $operand) {
		return '(' . $this->getParamNeedle($operand) . ' MEMBER OF ' . $this->getPropertyNameWithAlias($propertyName) . ')';
	}

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @return boolean
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName) {
		return '(' . $this->getPropertyNameWithAlias($propertyName) . ' IS EMPTY)';
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property
	 * @api
	 */
	public function in($propertyName, $operand) {
		// Take care: In cannot be needled at the moment! DQL escapes it, but only as literals, making caching a bit harder.
		// This is a todo for Doctrine 2.1
		return $this->queryBuilder->expr()->in($this->getPropertyNameWithAlias($propertyName), $operand);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		return $this->queryBuilder->expr()->lt($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		return $this->queryBuilder->expr()->lte($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		return $this->queryBuilder->expr()->gt($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		return $this->queryBuilder->expr()->gte($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Get a needle for parameter binding.
	 *
	 * @param mixed $operand
	 * @return string
	 */
	protected function getParamNeedle($operand) {
		$index = $this->parameterIndex++;
		$this->queryBuilder->setParameter($index, $operand);
		return '?' . $index;
	}

	/**
	 * Adds left join clauses along the given property path to the query, if needed.
	 * This enables us to set conditions on related objects.
	 *
	 * @param string $propertyPath The path to a sub property, e.g. property.subProperty.foo, or a simple property name
	 * @return string The last part of the property name prefixed by the used join alias, if joins have been added
	 */
	protected function getPropertyNameWithAlias($propertyPath) {
		$aliases = $this->queryBuilder->getRootAliases();
		$previousJoinAlias = $aliases[0];
		if (strpos($propertyPath, '.') === FALSE) {
			return $previousJoinAlias . '.' . $propertyPath;
		}

		$propertyPathParts = explode('.', $propertyPath);
		$conditionPartsCount = count($propertyPathParts);
		for ($i = 0; $i < $conditionPartsCount - 1; $i++) {
			$joinAlias = uniqid($propertyPathParts[$i]);
			$this->queryBuilder->leftJoin($previousJoinAlias . '.' . $propertyPathParts[$i], $joinAlias);
			$this->joins[$joinAlias] = $previousJoinAlias . '.' . $propertyPathParts[$i];
			$previousJoinAlias = $joinAlias;
		}

		return $previousJoinAlias . '.' . $propertyPathParts[$i];
	}

	/**
	 * We need to drop the query builder, as it contains a PDO instance deep inside.
	 *
	 * @return array
	 */
	public function __sleep() {
		$this->parameters = $this->queryBuilder->getParameters();
		return array('entityClassName', 'constraint', 'orderings', 'parameterIndex', 'limit', 'offset', 'parameters', 'joins');
	}

	/**
	 * Recreate query builder and set state again.
	 *
	 * @return void
	 */
	public function __wakeup() {
		if ($this->constraint !== NULL) {
			$this->queryBuilder->where($this->constraint);
		}

		if (is_array($this->orderings)) {
			$aliases = $this->queryBuilder->getRootAliases();
			foreach ($this->orderings AS $propertyName => $order) {
				$this->queryBuilder->addOrderBy($aliases[0] . '.' . $propertyName, $order);
			}
		}
		if (is_array($this->joins)) {
			foreach ($this->joins as $joinAlias => $join) {
				$this->queryBuilder->leftJoin($join, $joinAlias);
			}
		}
		$this->queryBuilder->setFirstResult($this->offset);
		$this->queryBuilder->setMaxResults($this->limit);
		$this->queryBuilder->setParameters($this->parameters);
		unset($this->parameters);
	}

	/**
	 * Cloning the query clones also the internal QueryBuilder,
	 * as they are tightly coupled.
	 */
	public function __clone() {
		$this->queryBuilder = clone $this->queryBuilder;
	}
}

namespace TYPO3\Flow\Persistence\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Query class for Doctrine 2
 */
class Query extends Query_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 * @param string $entityClassName
	 */
	public function __construct() {
		$arguments = func_get_args();

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $entityClassName in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Persistence\Doctrine\Query' === get_class($this)) {
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
	 * @return void
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
		return parent::__wakeup();
;
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
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Doctrine\Query', 'execute', $methodArguments, $adviceChain);
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
	 * @throws \TYPO3\Flow\Persistence\Doctrine\DatabaseConnectionException
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
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Persistence\Doctrine\Query', 'count', $methodArguments, $adviceChain);
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
		$this->injectEntityManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('Doctrine\Common\Persistence\ObjectManager'));
		$systemLogger_reference = &$this->systemLogger;
		$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SystemLoggerInterface');
		if ($this->systemLogger === NULL) {
			$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('6d57d95a1c3cd7528e3e6ea15012dac8', $systemLogger_reference);
			if ($this->systemLogger === NULL) {
				$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('6d57d95a1c3cd7528e3e6ea15012dac8',  $systemLogger_reference, 'TYPO3\Flow\Log\SystemLoggerInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'); });
			}
		}
	}
}
#