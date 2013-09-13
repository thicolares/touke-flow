<?php
namespace TYPO3\Flow\Session;

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
 * Implementation of a transient session.
 *
 * This session behaves like any other session except that it only stores the
 * data during one request.
 *
 * @Flow\Scope("singleton")
 */
class TransientSession_Original implements SessionInterface {

	/**
	 * The session Id
	 *
	 * @var string
	 */
	protected $sessionId;

	/**
	 * If this session has been started
	 *
	 * @var boolean
	 */
	protected $started = FALSE;

	/**
	 * The session data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var integer
	 */
	protected $lastActivityTimestamp;

	/**
	 * Tells if the session has been started already.
	 *
	 * @return boolean
	 */
	public function isStarted() {
		return $this->started;
	}

	/**
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 */
	public function start() {
		$this->sessionId = uniqid();
		$this->started = TRUE;
	}

	/**
	 * Returns TRUE if there is a session that can be resumed. FALSE otherwise
	 *
	 * @return boolean
	 */
	public function canBeResumed() {
		return TRUE;
	}

	/**
	 * Resumes an existing session, if any.
	 *
	 * @return void
	 */
	public function resume() {
		if ($this->started === FALSE) {
			$this->start();
		}
	}

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * @return string The new session ID
	 */
	public function renewId() {
		$this->sessionId = uniqid();
		return $this->sessionId;
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getId() {
		if ($this->started !== TRUE) throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034659);
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The data associated with the given key or NULL
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034660);
		return (array_key_exists($key, $this->data)) ? $this->data[$key] : NULL;
	}

	/**
	 * Returns TRUE if $key is available.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasKey($key) {
		return array_key_exists($key, $this->data);
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param object $data The data to be stored
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034661);
		$this->data[$key] = $data;
	}

	/**
	 * Closes the session
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function close() {
		if ($this->started !== TRUE) throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034662);
		$this->started = FALSE;
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @param string $reason A reason for destroying the session – used by the LoggingAspect
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function destroy($reason = NULL) {
		if ($this->started !== TRUE) throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034663);
		$this->data = array();
		$this->started = FALSE;
	}

	/**
	 * No operation for transient session.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function destroyAll(\TYPO3\Flow\Core\Bootstrap $bootstrap) {}

	/**
	 * No operation for transient session.
	 *
	 * @return void
	 */
	public function collectGarbage() {
	}

	/**
	 * Returns the unix time stamp marking the last point in time this session has
	 * been in use.
	 *
	 * @return integer unix timestamp
	 */
	public function getLastActivityTimestamp() {
		if ($this->lastActivityTimestamp === NULL) {
			$this->touch();
		}
		return $this->lastActivityTimestamp;
	}

	/**
	 * Updates the last activity time to "now".
	 *
	 * @return void
	 */
	public function touch() {
		$this->lastActivityTimestamp = time();
	}

}

namespace TYPO3\Flow\Session;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Implementation of a transient session.
 * 
 * This session behaves like any other session except that it only stores the
 * data during one request.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class TransientSession extends TransientSession_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Session\TransientSession') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Session\TransientSession', $this);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 protected function Flow_Aop_Proxy_buildMethodsAndAdvicesArray() {
		if (method_exists(get_parent_class($this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;
		$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
			'start' => array(
				'TYPO3\Flow\Aop\Advice\AfterAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterAdvice('TYPO3\Flow\Session\Aspect\LoggingAspect', 'logStart', $objectManager, NULL),
				),
			),
			'resume' => array(
				'TYPO3\Flow\Aop\Advice\AfterAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterAdvice('TYPO3\Flow\Session\Aspect\LoggingAspect', 'logResume', $objectManager, NULL),
				),
			),
			'destroy' => array(
				'TYPO3\Flow\Aop\Advice\BeforeAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\BeforeAdvice('TYPO3\Flow\Session\Aspect\LoggingAspect', 'logDestroy', $objectManager, NULL),
				),
			),
			'renewId' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LoggingAspect', 'logRenewId', $objectManager, NULL),
				),
			),
			'collectGarbage' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\Session\Aspect\LoggingAspect', 'logCollectGarbage', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Session\TransientSession') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Session\TransientSession', $this);

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
	 * @return void
	 */
	 public function start() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['start'])) {
		$result = parent::start();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['start'] = TRUE;
			try {
			
					$methodArguments = array();

		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'start', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['start']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'start', $joinPoint->getMethodArguments(), NULL, $result);
					$afterAdviceInvoked = TRUE;
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $exception) {

				if (!$afterAdviceInvoked) {
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['start']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'start', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['start']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['start']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 public function resume() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resume'])) {
		$result = parent::resume();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['resume'] = TRUE;
			try {
			
					$methodArguments = array();

		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'resume', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['resume']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'resume', $joinPoint->getMethodArguments(), NULL, $result);
					$afterAdviceInvoked = TRUE;
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $exception) {

				if (!$afterAdviceInvoked) {
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['resume']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'resume', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resume']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resume']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $reason A reason for destroying the session – used by the LoggingAspect
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	 public function destroy($reason = NULL) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['destroy'])) {
		$result = parent::destroy($reason);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['destroy'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['reason'] = $reason;
			
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['destroy']['TYPO3\Flow\Aop\Advice\BeforeAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'destroy', $methodArguments);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'destroy', $joinPoint->getMethodArguments());
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['destroy']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['destroy']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return string The new session ID
	 */
	 public function renewId() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['renewId'])) {
		$result = parent::renewId();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['renewId'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('renewId');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'renewId', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['renewId']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['renewId']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 public function collectGarbage() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['collectGarbage'])) {
		$result = parent::collectGarbage();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['collectGarbage'] = TRUE;
			try {
			
					$methodArguments = array();

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'collectGarbage', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['collectGarbage']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Session\TransientSession', 'collectGarbage', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['collectGarbage']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['collectGarbage']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Session\TransientSession');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Session\TransientSession', $propertyName, 'transient')) continue;
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
}
#