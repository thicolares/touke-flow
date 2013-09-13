<?php
namespace TYPO3\Flow\Security\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Proxy\Proxy;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Persistence\EmptyQueryResult;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException;

/**
 * An aspect which rewrites persistence query to filter objects one should not be able to retrieve.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PersistenceQueryRewritingAspect_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Array of registered global objects that can be accessed as operands
	 * @var array
	 */
	protected $globalObjects = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $alreadyRewrittenQueries;

	/**
	 * Inject global settings, retrieves the registered global objects that might be used as operands
	 *
	 * @param array $settings The current Flow settings
	 * @return void
	 */
	public function injectSettings($settings) {
		$this->globalObjects = $settings['aop']['globalObjects'];
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->alreadyRewrittenQueries = new \SplObjectStorage();
	}

	/**
	 * Rewrites the QOM query, by adding appropriate constraints according to the policy
	 *
	 * @Flow\Around("setting(TYPO3.Flow.security.enable) && within(TYPO3\Flow\Persistence\QueryInterface) && method(.*->(execute|count)())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed
	 */
	public function rewriteQomQuery(JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->securityContext->areAuthorizationChecksDisabled() === TRUE || $this->policyService->hasPolicyEntriesForEntities() === FALSE) {
			return $result;
		}
		if ($this->securityContext->isInitialized() === FALSE) {
			if ($this->securityContext->canBeInitialized() === TRUE) {
				$this->securityContext->initialize();
			} else {
				return $result;
			}
		}

		/** @var $query QueryInterface */
		$query = $joinPoint->getProxy();

		if ($this->alreadyRewrittenQueries->contains($query)) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} else {
			$this->alreadyRewrittenQueries->attach($query);
		}

		$entityType = $query->getType();
		$authenticatedRoles = $this->securityContext->getRoles();

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) {
				return ($joinPoint->getMethodName() === 'count') ? 0 : new EmptyQueryResult($query);
			}
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			$additionalCalculatedConstraints = $this->getQomConstraintForConstraintDefinitions($policyConstraintsDefinition, $query);

			if ($query->getConstraint() !== NULL && $additionalCalculatedConstraints !== NULL) {
				$query->matching($query->logicalAnd($query->getConstraint(), $additionalCalculatedConstraints));
			} elseif ($additionalCalculatedConstraints !== NULL) {
				$query->matching($additionalCalculatedConstraints);
			}
		}

		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * Checks, if the current policy allows the retrieval of the object fetched by getObjectDataByIdentifier()
	 *
	 * @Flow\Around("within(TYPO3\Flow\Persistence\PersistenceManagerInterface) && method(.*->getObjectByIdentifier()) && setting(TYPO3.Flow.security.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return array The object data of the original object, or NULL if access is not permitted
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifier(JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->securityContext->areAuthorizationChecksDisabled() === TRUE || $this->policyService->hasPolicyEntriesForEntities() === FALSE) {
			return $result;
		}
		if ($this->securityContext->isInitialized() === FALSE) {
			if ($this->securityContext->canBeInitialized() === TRUE) {
				$this->securityContext->initialize();
			} else {
				return $result;
			}
		}

		$authenticatedRoles = $this->securityContext->getRoles();

		if ($result instanceof Proxy) {
			$entityType = get_parent_class($result);
		} else {
			$entityType = get_class($result);
		}

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) return NULL;
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			if ($this->checkConstraintDefinitionsOnResultObject($policyConstraintsDefinition, $result) === FALSE) return NULL;
		}

		return $result;
	}

	/**
	 * Builds a QOM constraint object for an array of constraint expressions
	 *
	 * @param array $constraintDefinitions The constraint expressions
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint The build constraint object
	 */
	protected function getQomConstraintForConstraintDefinitions(array $constraintDefinitions, QueryInterface $query) {
		$resourceConstraintObjects = array();
		foreach ($constraintDefinitions as $resourceConstraintsDefinition) {
			$resourceConstraintObject = NULL;
			foreach ($resourceConstraintsDefinition as $operator => $policyConstraintsDefinition) {
				foreach ($policyConstraintsDefinition as $key => $singlePolicyConstraintDefinition) {
					if ($key === 'subConstraints') {
						$currentConstraint = $this->getQomConstraintForConstraintDefinitions(array($singlePolicyConstraintDefinition), $query);
					} else {
						$currentConstraint = $this->getQomConstraintForSingleConstraintDefinition($singlePolicyConstraintDefinition, $query);
					}

					if ($resourceConstraintObject === NULL) {
						$resourceConstraintObject = $currentConstraint;
						continue;
					}

					switch ($operator) {
						case '&&':
							$resourceConstraintObject = $query->logicalAnd($resourceConstraintObject, $currentConstraint);
							break;
						case '&&!':
							$resourceConstraintObject = $query->logicalAnd($resourceConstraintObject, $query->logicalNot($currentConstraint));
							break;
						case '||':
							$resourceConstraintObject = $query->logicalOr($resourceConstraintObject, $currentConstraint);
							break;
						case '||!':
							$resourceConstraintObject = $query->logicalOr($resourceConstraintObject, $query->logicalNot($currentConstraint));
							break;
					}
				}
			}
			$resourceConstraintObjects[] = $query->logicalNot($resourceConstraintObject);
		}

		if (count($resourceConstraintObjects) > 1) {
			return $query->logicalAnd($resourceConstraintObjects);
		} elseif (count($resourceConstraintObjects) === 1) {
			return current($resourceConstraintObjects);
		} else {
			return NULL;
		}
	}

	/**
	 * Builds a QOM constraint object for one single constraint expression
	 *
	 * @param array $constraintDefinition The constraint expression
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint The build constraint object
	 * @throws \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	protected function getQomConstraintForSingleConstraintDefinition(array $constraintDefinition, QueryInterface $query) {
		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['leftValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['rightValue']);
		} else if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['rightValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['leftValue']);
		} else {
			throw new InvalidQueryRewritingConstraintException('An entity constraint has to have one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1267881842);
		}

		switch ($constraintDefinition['operator']) {
			case '==':
				return $query->equals($propertyName, $operand);
				break;
			case '!=':
				return $query->logicalNot($query->equals($propertyName, $operand));
				break;
			case '<':
				return $query->lessThan($propertyName, $operand);
				break;
			case '>':
				return $query->greaterThan($propertyName, $operand);
				break;
			case '<=':
				return $query->lessThanOrEqual($propertyName, $operand);
				break;
			case '>=':
				return $query->greaterThanOrEqual($propertyName, $operand);
				break;
			case 'in':
				return $query->in($propertyName, $operand);
				break;
			case 'contains':
				return $query->contains($propertyName, $operand);
				break;
			case 'matches':
				$compositeConstraint = NULL;
				foreach ($operand as $operandEntry) {
					$currentConstraint = $query->contains($propertyName, $operandEntry);

					if ($compositeConstraint === NULL) {
						$compositeConstraint = $currentConstraint;
						continue;
					}

					$compositeConstraint = $query->logicalAnd($currentConstraint, $compositeConstraint);
				}

				return $compositeConstraint;
				break;
		}

		throw new InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1270483540);
	}

	/**
	 * Checks, if the given constraints hold for the passed result.
	 *
	 * @param array $constraintDefinitions The constraint definitions array
	 * @param object $result The result object returned by the persistence manager
	 * @return boolean TRUE if the query result is valid for the given constraint
	 */
	protected function checkConstraintDefinitionsOnResultObject(array $constraintDefinitions, $result) {
		foreach ($constraintDefinitions as $resourceConstraintsDefinition) {
			$resourceResult = TRUE;
			foreach ($resourceConstraintsDefinition as $operator => $policyConstraintsDefinition) {
				foreach ($policyConstraintsDefinition as $key => $singlePolicyConstraintDefinition) {
					if ($key === 'subConstraints') {
						$currentResult = $this->checkConstraintDefinitionsOnResultObject(array($singlePolicyConstraintDefinition), $result);
					} else {
						$currentResult = $this->checkSingleConstraintDefinitionOnResultObject($singlePolicyConstraintDefinition, $result);
					}

					switch ($operator) {
						case '&&':
							$resourceResult = $currentResult && $resourceResult;
							break;
						case '&&!':
							$resourceResult = (!$currentResult) && $resourceResult;
							break;
						case '||':
							$resourceResult = $currentResult || $resourceResult;
							break;
						case '||!':
							$resourceResult = (!$currentResult) && $resourceResult;
							break;
					}
				}
			}

			if ($resourceResult === TRUE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Checks, if the given constraint holds for the passed result.
	 *
	 * @param array $constraintDefinition The constraint definition array
	 * @param object $result The result object returned by the persistence manager
	 * @return boolean TRUE if the query result is valid for the given constraint
	 * @throws \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	protected function checkSingleConstraintDefinitionOnResultObject(array $constraintDefinition, $result) {
		$referenceToThisFound = FALSE;

		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['leftValue'], 5);
			$leftOperand = $this->getObjectValueByPath($result, $propertyPath);
		} else {
			$leftOperand = $this->getValueForOperand($constraintDefinition['leftValue']);
		}

		if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['rightValue'], 5);
			$rightOperand = $this->getObjectValueByPath($result, $propertyPath);
		} else {
			$rightOperand = $this->getValueForOperand($constraintDefinition['rightValue']);
		}

		if ($referenceToThisFound === FALSE) {
			throw new InvalidQueryRewritingConstraintException('An entity security constraint must have at least one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1277218400);
		}

		if (is_object($leftOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'TYPO3\Flow\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'Doctrine\ORM\Mapping\Entity')
			)
		) {
			$leftOperand = $this->persistenceManager->getIdentifierByObject($leftOperand);
		}

		if (is_object($rightOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($rightOperand), 'TYPO3\Flow\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($rightOperand), 'Doctrine\ORM\Mapping\Entity')
			)
		) {
			$rightOperand = $this->persistenceManager->getIdentifierByObject($rightOperand);
		}

		switch ($constraintDefinition['operator']) {
			case '!=':
				return ($leftOperand !== $rightOperand);
				break;
			case '==':
				return ($leftOperand === $rightOperand);
				break;
			case '<':
				return ($leftOperand < $rightOperand);
				break;
			case '>':
				return ($leftOperand > $rightOperand);
				break;
			case '<=':
				return ($leftOperand <= $rightOperand);
				break;
			case '>=':
				return ($leftOperand >= $rightOperand);
				break;
			case 'in':
				return in_array($leftOperand, $rightOperand);
				break;
			case 'contains':
				return in_array($rightOperand, $leftOperand);
				break;
			case 'matches':
				return (count(array_intersect($leftOperand, $rightOperand)) !== 0);
				break;
		}

		throw new InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1277222521);
	}

	/**
	 * Returns the static value of the given operand, this might be also a global object
	 *
	 * @param mixed $expression The expression string representing the operand
	 * @return mixed The calculated value
	 */
	protected function getValueForOperand($expression) {
		if (is_array($expression)) {
			$result = array();
			foreach ($expression as $expressionEntry) {
				$result[] = $this->getValueForOperand($expressionEntry);
			}
			return $result;
		} else if (is_numeric($expression)) {
			return $expression;
		} else if ($expression === 'TRUE') {
			return TRUE;
		} else if ($expression === 'FALSE') {
			return FALSE;
		} else if ($expression === 'NULL') {
			return NULL;
		} else if (strpos($expression, 'current.') === 0) {
			$objectAccess = explode('.', $expression, 3);
			$globalObjectsRegisteredClassName = $this->globalObjects[$objectAccess[1]];
			$globalObject = $this->objectManager->get($globalObjectsRegisteredClassName);
			return $this->getObjectValueByPath($globalObject, $objectAccess[2]);
		} else {
			return trim($expression, '"\'');
		}
	}

	/**
	 * Redirects directly to \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($result, $propertyPath)
	 * This is only needed for unit tests!
	 *
	 * @param mixed $object The object to fetch the property from
	 * @param string $path The path to the property to be fetched
	 * @return mixed The property value
	 */
	protected function getObjectValueByPath($object, $path) {
		return ObjectAccess::getPropertyPath($object, $path);
	}
}

namespace TYPO3\Flow\Security\Aspect;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which rewrites persistence query to filter objects one should not be able to retrieve.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 * @\TYPO3\Flow\Annotations\Aspect
 */
class PersistenceQueryRewritingAspect extends PersistenceQueryRewritingAspect_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', $this);
		parent::__construct();
		if ('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', $propertyName, 'transient')) continue;
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
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$policyService_reference = &$this->policyService;
		$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Policy\PolicyService');
		if ($this->policyService === NULL) {
			$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('16231078e783810895dba92e364c25f7', $policyService_reference);
			if ($this->policyService === NULL) {
				$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('16231078e783810895dba92e364c25f7',  $policyService_reference, 'TYPO3\Flow\Security\Policy\PolicyService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Policy\PolicyService'); });
			}
		}
		$securityContext_reference = &$this->securityContext;
		$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Context');
		if ($this->securityContext === NULL) {
			$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('48836470c14129ade5f39e28c4816673', $securityContext_reference);
			if ($this->securityContext === NULL) {
				$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('48836470c14129ade5f39e28c4816673',  $securityContext_reference, 'TYPO3\Flow\Security\Context', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Context'); });
			}
		}
		$session_reference = &$this->session;
		$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Session\SessionInterface');
		if ($this->session === NULL) {
			$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('3055dab6d586d9b0b7e34ad0e5d2b702', $session_reference);
			if ($this->session === NULL) {
				$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('3055dab6d586d9b0b7e34ad0e5d2b702',  $session_reference, 'TYPO3\Flow\Session\SessionInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Session\SessionInterface'); });
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
		$persistenceManager_reference = &$this->persistenceManager;
		$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		if ($this->persistenceManager === NULL) {
			$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('f1bc82ad47156d95485678e33f27c110', $persistenceManager_reference);
			if ($this->persistenceManager === NULL) {
				$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('f1bc82ad47156d95485678e33f27c110',  $persistenceManager_reference, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'); });
			}
		}
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
	}
}
#