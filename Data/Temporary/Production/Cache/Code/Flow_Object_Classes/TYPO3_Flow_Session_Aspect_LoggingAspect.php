<?php
namespace TYPO3\Flow\Session\Aspect;

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
 * An aspect which centralizes the logging of important session actions.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class LoggingAspect_Original {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of start()
	 *
	 * @Flow\After("within(TYPO3\Flow\Session\SessionInterface) && method(.*->start())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logStart(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('%s: Started session with id %s.', $this->getClassName($joinPoint), $session->getId()), LOG_INFO);
		}
	}

	/**
	 * Logs calls of resume()
	 *
	 * @Flow\After("within(TYPO3\Flow\Session\SessionInterface) && method(.*->resume())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logResume(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$inactivityInSeconds = $joinPoint->getResult();
			if ($inactivityInSeconds === 1) {
				$inactivityMessage = '1 second';
			} elseif ($inactivityInSeconds < 120) {
				$inactivityMessage = sprintf('%s seconds', $inactivityInSeconds);
			} elseif ($inactivityInSeconds < 3600) {
				$inactivityMessage = sprintf('%s minutes', intval($inactivityInSeconds / 60));
			} elseif ($inactivityInSeconds < 7200) {
				$inactivityMessage = 'more than an hour';
			} else {
				$inactivityMessage = sprintf('more than %s hours', intval($inactivityInSeconds / 3600));
			}
			$this->systemLogger->log(sprintf('%s: Resumed session with id %s which was inactive for %s. (%ss)', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $inactivityMessage, $inactivityInSeconds), LOG_DEBUG);
		}
	}

	/**
	 * Logs calls of destroy()
	 *
	 * @Flow\Before("within(TYPO3\Flow\Session\SessionInterface) && method(.*->destroy())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logDestroy(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		if ($session->isStarted()) {
			$reason = $joinPoint->isMethodArgument('reason') ? $joinPoint->getMethodArgument('reason') : 'no reason given';
			$this->systemLogger->log(sprintf('%s: Destroyed session with id %s: %s', $this->getClassName($joinPoint), $joinPoint->getProxy()->getId(), $reason), LOG_INFO);
		}
	}

	/**
	 * Logs calls of renewId()
	 *
	 * @Flow\Around("within(TYPO3\Flow\Session\SessionInterface) && method(.*->renewId())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logRenewId(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$session = $joinPoint->getProxy();
		$oldId = $session->getId();
		$newId = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($session->isStarted()) {
			$this->systemLogger->log(sprintf('%s: Changed session id from %s to %s', $this->getClassName($joinPoint), $oldId, $newId), LOG_INFO);
		}
		return $newId;
	}

	/**
	 * Logs calls of collectGarbage()
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Session\SessionInterface) && method(.*->collectGarbage())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 */
	public function logCollectGarbage(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$sessionRemovalCount = $joinPoint->getResult();
		if ($sessionRemovalCount > 0) {
			$this->systemLogger->log(sprintf('%s: Triggered garbage collection and removed %s expired sessions.', $this->getClassName($joinPoint), $sessionRemovalCount), LOG_INFO);
		} elseif ($sessionRemovalCount === 0) {
			$this->systemLogger->log(sprintf('%s: Triggered garbage collection but no sessions needed to be removed.', $this->getClassName($joinPoint)), LOG_INFO);
		} elseif ($sessionRemovalCount === FALSE) {
			$this->systemLogger->log(sprintf('%s: Ommitting garbage collection because another process is already running. Consider lowering the GC propability if these messages appear a lot.', $this->getClassName($joinPoint)), LOG_WARNING);
		}
	}

	/**
	 * Determines the short or full class name of the session implementation
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	protected function getClassName(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$className = $joinPoint->getClassName();
		if (substr($className, 0, 18) === 'TYPO3\Flow\Session') {
			$className = substr($className, 19);
		}
		return $className;
	}

}

namespace TYPO3\Flow\Session\Aspect;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which centralizes the logging of important session actions.
 * @\TYPO3\Flow\Annotations\Aspect
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class LoggingAspect extends LoggingAspect_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Session\Aspect\LoggingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Session\Aspect\LoggingAspect', $this);
		if ('TYPO3\Flow\Session\Aspect\LoggingAspect' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Session\Aspect\LoggingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Session\Aspect\LoggingAspect', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Session\Aspect\LoggingAspect');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Session\Aspect\LoggingAspect', $propertyName, 'transient')) continue;
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