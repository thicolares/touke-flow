<?php
namespace TYPO3\Flow\Security\Authorization;

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
 * An access decision voter manager
 *
 * @Flow\Scope("singleton")
 */
class AccessDecisionVoterManager_Original implements AccessDecisionManagerInterface {

	/**
	 * The object manager
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The current security context
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * Array of \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects
	 * @var array
	 */
	protected $accessDecisionVoters = array();

	/**
	 * If set to TRUE access will be granted for objects where all voters abstain from decision.
	 * @var boolean
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager The object manager
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context
	 */
	public function __construct(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager, \TYPO3\Flow\Security\Context $securityContext) {
		$this->objectManager = $objectManager;
		$this->securityContext = $securityContext;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->createAccessDecisionVoters($settings['security']['authorization']['accessDecisionVoters']);
		$this->allowAccessIfAllAbstain = $settings['security']['authorization']['allowAccessIfAllVotersAbstain'];
	}

	/**
	 * Returns the configured access decision voters
	 *
	 * @return array Array of \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects
	 */
	public function getAccessDecisionVoters() {
		return $this->accessDecisionVoters;
	}

	/**
	 * Decides if access should be granted on the given object in the current security context.
	 * It iterates over all available \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$denyVotes = 0;
		$grantVotes = 0;
		$abstainVotes = 0;

		foreach ($this->accessDecisionVoters as $voter) {
			$vote = $voter->voteForJoinPoint($this->securityContext, $joinPoint);
			switch ($vote) {
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY:
					$denyVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT:
					$grantVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN:
					$abstainVotes++;
					break;
			}
		}

		if ($denyVotes === 0 && $grantVotes > 0) {
			return;
		}
		if ($denyVotes === 0 && $grantVotes === 0 && $abstainVotes > 0 && $this->allowAccessIfAllAbstain === TRUE) {
			return;
		}

		$votes = sprintf('(%d denied, %d granted, %d abstained)', $denyVotes, $grantVotes, $abstainVotes);
		throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied ' . $votes, 1222268609);
	}

	/**
	 * Decides if access should be granted on the given resource in the current security context.
	 * It iterates over all available \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param string $resource The resource to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnResource($resource) {
		$denyVotes = 0;
		$grantVotes = 0;
		$abstainVotes = 0;

		foreach ($this->accessDecisionVoters as $voter) {
			$vote = $voter->voteForResource($this->securityContext, $resource);
			switch ($vote) {
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY:
					$denyVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT:
					$grantVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN:
					$abstainVotes++;
					break;
			}
		}

		if ($denyVotes === 0 && $grantVotes > 0) {
			return;
		}
		if ($denyVotes === 0 && $grantVotes === 0 && $abstainVotes > 0 && $this->allowAccessIfAllAbstain === TRUE) {
			return;
		}

		$votes = sprintf('(%d denied, %d granted, %d abstained)', $denyVotes, $grantVotes, $abstainVotes);
		throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied ' . $votes, 1283175927);
	}

	/**
	 * Creates and sets the configured access decision voters
	 *
	 * @param array $voterClassNames Array of access decision voter class names
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\VoterNotFoundException
	 */
	protected function createAccessDecisionVoters(array $voterClassNames) {
		foreach ($voterClassNames as $voterClassName) {
			if (!$this->objectManager->isRegistered($voterClassName)) throw new \TYPO3\Flow\Security\Exception\VoterNotFoundException('No voter of type ' . $voterClassName . ' found!', 1222267934);

			$voter = $this->objectManager->get($voterClassName);
			if (!($voter instanceof \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface)) throw new \TYPO3\Flow\Security\Exception\VoterNotFoundException('The found voter class did not implement \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', 1222268008);

			$this->accessDecisionVoters[] = $voter;
		}
	}
}

namespace TYPO3\Flow\Security\Authorization;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An access decision voter manager
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class AccessDecisionVoterManager extends AccessDecisionVoterManager_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager The object manager
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context
	 */
	public function __construct() {
		$arguments = func_get_args();

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface', $this);

		if (!array_key_exists(0, $arguments)) $arguments[0] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface');
		if (!array_key_exists(1, $arguments)) $arguments[1] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Context');
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $objectManager in class ' . __CLASS__ . '. Please check your calling code and Dependency Injection configuration.', 1296143787);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $securityContext in class ' . __CLASS__ . '. Please check your calling code and Dependency Injection configuration.', 1296143787);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager' === get_class($this)) {
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
			'decideOnJoinPoint' => array(
				'TYPO3\Flow\Aop\Advice\AfterThrowingAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterThrowingAdvice('TYPO3\Flow\Security\Aspect\LoggingAspect', 'logJoinPointAccessDecisions', $objectManager, NULL),
				),
			),
			'decideOnResource' => array(
				'TYPO3\Flow\Aop\Advice\AfterThrowingAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterThrowingAdvice('TYPO3\Flow\Security\Aspect\LoggingAspect', 'logResourceAccessDecisions', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface', $this);

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
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	 public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnJoinPoint'])) {
		$result = parent::decideOnJoinPoint($joinPoint);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnJoinPoint'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['joinPoint'] = $joinPoint;
			
		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', 'decideOnJoinPoint', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

			} catch (\Exception $exception) {

				$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['decideOnJoinPoint']['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice'];
				$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', 'decideOnJoinPoint', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
				foreach ($advices as $advice) {
					$advice->invoke($joinPoint);
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnJoinPoint']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnJoinPoint']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $resource The resource to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	 public function decideOnResource($resource) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnResource'])) {
		$result = parent::decideOnResource($resource);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnResource'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['resource'] = $resource;
			
		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', 'decideOnResource', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

			} catch (\Exception $exception) {

				$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['decideOnResource']['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice'];
				$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', 'decideOnResource', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
				foreach ($advices as $advice) {
					$advice->invoke($joinPoint);
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnResource']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['decideOnResource']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', $propertyName, 'transient')) continue;
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
	}
}
#