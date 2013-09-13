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

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which centralizes the logging of security relevant actions.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class LoggingAspect_Original {

	/**
	 * @var \TYPO3\Flow\Log\SecurityLoggerInterface
	 * @Flow\Inject
	 */
	protected $securityLogger;

	/**
	 * @var boolean
	 */
	protected $alreadyLoggedAuthenticateCall = FALSE;

	/**
	 * Logs calls and results of the authenticate() method of the Authentication Manager
	 *
	 * @Flow\After("within(TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface) && method(.*->authenticate())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @throws \Exception
	 */
	public function logManagerAuthenticate(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if ($joinPoint->hasException()) {
			$exception = $joinPoint->getException();
			$this->securityLogger->log('Authentication failed: "' . $exception->getMessage() . '" #' . $exception->getCode(), LOG_NOTICE);
			throw $exception;
		} elseif ($this->alreadyLoggedAuthenticateCall === FALSE) {
			if ($joinPoint->getProxy()->getSecurityContext()->getAccount() !== NULL) {
				$this->securityLogger->log('Successfully re-authenticated tokens for account "' . $joinPoint->getProxy()->getSecurityContext()->getAccount()->getAccountIdentifier() . '"', LOG_INFO);
			} else {
				$this->securityLogger->log('No account authenticated', LOG_INFO);
			}
			$this->alreadyLoggedAuthenticateCall = TRUE;
		}
	}

	/**
	 * Logs calls and results of the logout() method of the Authentication Manager
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface) && method(.*->logout())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logManagerLogout(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$securityContext = $joinPoint->getProxy()->getSecurityContext();
		if (!$securityContext->isInitialized()) {
			return;
		}
		$accountIdentifiers = array();
		foreach ($securityContext->getAuthenticationTokens() as $token) {
			$account = $token->getAccount();
			if ($account !== NULL) {
				$accountIdentifiers[] = $account->getAccountIdentifier();
			}
		}
		$this->securityLogger->log('Logged out ' . count($accountIdentifiers) . ' account(s). (' . implode(', ', $accountIdentifiers) . ')', LOG_INFO);
	}

	/**
	 * Logs calls and results of the authenticate() method of an authentication provider
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface) && method(.*->authenticate())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logPersistedUsernamePasswordProviderAuthenticate(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$token = $joinPoint->getMethodArgument('authenticationToken');

		switch ($token->getAuthenticationStatus()) {
			case \TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL :
				$this->securityLogger->log('Successfully authenticated token: ' . $token, LOG_NOTICE, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
				$this->alreadyLoggedAuthenticateCall = TRUE;
			break;
			case \TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS :
				$this->securityLogger->log('Wrong credentials given for token: ' . $token, LOG_WARNING, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
			case \TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN :
				$this->securityLogger->log('No credentials given or no account found for token: ' . $token, LOG_WARNING, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
		}
	}

	/**
	 * Logs calls and results of decideOnJoinPoint()
	 *
	 * @Flow\AfterThrowing("method(TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager->decideOnJoinPoint())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @throws \Exception
	 * @return void
	 */
	public function logJoinPointAccessDecisions(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$exception = $joinPoint->getException();
		$subjectJoinPoint = $joinPoint->getMethodArgument('joinPoint');
		$message = $exception->getMessage() . ' to method ' . $subjectJoinPoint->getClassName() . '::' . $subjectJoinPoint->getMethodName() . '().';
		$this->securityLogger->log($message, \LOG_INFO);

		throw $exception;
	}

	/**
	 * Logs calls and results of decideOnResource()
	 *
	 * @Flow\AfterThrowing("method(TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager->decideOnResource())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @throws \Exception
	 * @return void
	 */
	public function logResourceAccessDecisions(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$exception = $joinPoint->getException();
		$message = $exception->getMessage() . ' on resource "' . $joinPoint->getMethodArgument('resource') . '".';
		$this->securityLogger->log($message, \LOG_INFO);

		throw $exception;
	}
}

namespace TYPO3\Flow\Security\Aspect;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which centralizes the logging of security relevant actions.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 * @\TYPO3\Flow\Annotations\Aspect
 */
class LoggingAspect extends LoggingAspect_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\LoggingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\LoggingAspect', $this);
		if ('TYPO3\Flow\Security\Aspect\LoggingAspect' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\LoggingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\LoggingAspect', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Aspect\LoggingAspect');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Aspect\LoggingAspect', $propertyName, 'transient')) continue;
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
		$securityLogger_reference = &$this->securityLogger;
		$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SecurityLoggerInterface');
		if ($this->securityLogger === NULL) {
			$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('cf5d8e4c29f4b5ca11e319496c806b88', $securityLogger_reference);
			if ($this->securityLogger === NULL) {
				$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('cf5d8e4c29f4b5ca11e319496c806b88',  $securityLogger_reference, 'TYPO3\Flow\Log\Logger', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SecurityLoggerInterface'); });
			}
		}
	}
}
#