<?php
namespace TYPO3\Flow\Mvc\Routing\Aspect;

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
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class RouterCachingAspect_Original {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 * @Flow\Inject
	 */
	protected $findMatchResultsCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 * @Flow\Inject
	 */
	protected $resolveCache;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->findMatchResults())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function cacheMatchingCall(JoinPointInterface $joinPoint) {
		/** @var $httpRequest \TYPO3\Flow\Http\Request */
		$httpRequest = $joinPoint->getMethodArgument('httpRequest');
		$routePath = substr($httpRequest->getUri()->getPath(), strlen($httpRequest->getBaseUri()->getPath()));

		$cacheIdentifier = md5($routePath) . '_' . $httpRequest->getMethod();
		$cachedResult = $this->findMatchResultsCache->get($cacheIdentifier);
		if ($cachedResult !== FALSE) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $cacheIdentifier, $routePath), LOG_DEBUG);
			return $cachedResult;
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$matchedRoute = $joinPoint->getProxy()->getLastMatchedRoute();
		if ($matchedRoute !== NULL) {
			$this->systemLogger->log(sprintf('Router route(): Route "%s" matched the path "%s".', $matchedRoute->getName(), $routePath), LOG_DEBUG);
		} else {
			$this->systemLogger->log(sprintf('Router route(): No route matched the route path "%s".', $routePath), LOG_NOTICE);
		}
		if ($matchResults !== NULL && $this->containsObject($matchResults) === FALSE) {
			$this->findMatchResultsCache->set($cacheIdentifier, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->resolve())")
	 * @param JoinPointInterface $joinPoint The current join point
	 * @return string Result of the target method
	 */
	public function cacheResolveCall(JoinPointInterface $joinPoint) {
		$cacheIdentifier = NULL;
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		try {
			$routeValues = $this->convertObjectsToHashes($routeValues);
			\TYPO3\Flow\Utility\Arrays::sortKeysRecursively($routeValues);
			$cacheIdentifier = md5(http_build_query($routeValues));
			$cachedResult = $this->resolveCache->get($cacheIdentifier);
			if ($cachedResult !== FALSE) {
				return $cachedResult;
			}
		} catch (\InvalidArgumentException $exception) {
		}

		$matchingUri = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUri !== NULL && $cacheIdentifier !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $matchingUri);
		}
		return $matchingUri;
	}

	/**
	 * Flushes 'findMatchResults' and 'resolve' caches.
	 *
	 * @return void
	 */
	public function flushCaches() {
		$this->findMatchResultsCache->flush();
		$this->resolveCache->flush();
	}

	/**
	 * Checks if the given subject contains an object
	 *
	 * @param mixed $subject
	 * @return boolean If it contains an object or not
	 */
	protected function containsObject($subject) {
		if (is_object($subject)) {
			return TRUE;
		}
		if (!is_array($subject)) {
			return FALSE;
		}
		foreach ($subject as $value) {
			if ($this->containsObject($value)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Recursively converts objects in an array to their identifiers
	 *
	 * @param array $routeValues the array to be processed
	 * @return array the modified array
	 * @throws \InvalidArgumentException if $routeValues contain an object and its identifier could not be determined
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				$identifier = $this->persistenceManager->getIdentifierByObject($value);
				if ($identifier === NULL) {
					throw new \InvalidArgumentException(sprintf('The identifier of an object of type "%s" could not be determined', get_class($value)), 1340102526);
				}
				$value = $identifier;
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
			}
		}
		return $routeValues;
	}
}
namespace TYPO3\Flow\Mvc\Routing\Aspect;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 * @\TYPO3\Flow\Annotations\Aspect
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class RouterCachingAspect extends RouterCachingAspect_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', $this);
		if ('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', $propertyName, 'transient')) continue;
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
		$findMatchResultsCache_reference = &$this->findMatchResultsCache;
		$this->findMatchResultsCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('');
		if ($this->findMatchResultsCache === NULL) {
			$this->findMatchResultsCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('c2edf19da7c3c01810819eb4af8e9fc9', $findMatchResultsCache_reference);
			if ($this->findMatchResultsCache === NULL) {
				$this->findMatchResultsCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('c2edf19da7c3c01810819eb4af8e9fc9',  $findMatchResultsCache_reference, '', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Mvc_Routing_FindMatchResults'); });
			}
		}
		$resolveCache_reference = &$this->resolveCache;
		$this->resolveCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('');
		if ($this->resolveCache === NULL) {
			$this->resolveCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('945ae5d4f12ecd95f7db31e14a26896e', $resolveCache_reference);
			if ($this->resolveCache === NULL) {
				$this->resolveCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('945ae5d4f12ecd95f7db31e14a26896e',  $resolveCache_reference, '', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Mvc_Routing_Resolve'); });
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