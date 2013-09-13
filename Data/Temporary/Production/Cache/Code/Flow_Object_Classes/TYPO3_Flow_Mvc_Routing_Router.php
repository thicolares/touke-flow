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
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Flow\Utility\Arrays;

/**
 * The default web router
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Router_Original implements RouterInterface {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * @var string
	 */
	protected $controllerObjectNamePattern = '@package\@subpackage\Controller\@controllerController';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Array containing the configuration for all routes.
	 * @var array
	 */
	protected $routesConfiguration = array();

	/**
	 * Array of routes to match against
	 * @var array
	 */
	protected $routes = array();

	/**
	 * TRUE if route object have been created, otherwise FALSE
	 * @var boolean
	 */
	protected $routesCreated = FALSE;

	/**
	 * The current request. Will be set in route()
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $actionRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\Route
	 */
	protected $lastMatchedRoute;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\Route
	 */
	protected $lastResolvedRoute;

	/**
	 * Sets the routes configuration.
	 *
	 * @param array $routesConfiguration The routes configuration
	 * @return void
	 */
	public function setRoutesConfiguration(array $routesConfiguration) {
		$this->routesConfiguration = $routesConfiguration;
		$this->routesCreated = FALSE;
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest The web request to be analyzed. Will be modified by the router.
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function route(Request $httpRequest) {
		$this->actionRequest = $httpRequest->createActionRequest();

		$matchResults = $this->findMatchResults($httpRequest);
		if ($matchResults !== NULL) {
			$requestArguments = $this->actionRequest->getArguments();
			$mergedArguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $matchResults);
			$this->actionRequest->setArguments($mergedArguments);
		}
		$this->setDefaultControllerAndActionNameIfNoneSpecified();
		return $this->actionRequest;
	}

	/**
	 * Returns the route that has been matched with the last route() call.
	 * Returns NULL if no route matched or route() has not been called yet
	 *
	 * @return Route
	 */
	public function getLastMatchedRoute() {
		return $this->lastMatchedRoute;
	}

	/**
	 * Returns a list of configured routes
	 *
	 * @return array
	 */
	public function getRoutes() {
		$this->createRoutesFromConfiguration();
		return $this->routes;
	}

	/**
	 * Manually adds a route to the beginning of the configured routes
	 *
	 * @param \TYPO3\Flow\Mvc\Routing\Route $route
	 * @return void
	 */
	public function addRoute(Route $route) {
		$this->createRoutesFromConfiguration();
		array_unshift($this->routes, $route);
	}

	/**
	 * Set the default controller and action names if none has been specified.
	 *
	 * @return void
	 */
	protected function setDefaultControllerAndActionNameIfNoneSpecified() {
		if ($this->actionRequest->getControllerName() === NULL) {
			$this->actionRequest->setControllerName('Standard');
		}
		if ($this->actionRequest->getControllerActionName() === NULL) {
			$this->actionRequest->setControllerActionName('index');
		}
	}

	/**
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return array results of the matching route
	 * @see route()
	 */
	protected function findMatchResults(Request $httpRequest) {
		$this->lastMatchedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->matches($httpRequest) === TRUE) {
				$this->lastMatchedRoute = $route;
				return $route->getMatchResults();
			}
		}
		return NULL;
	}

	/**
	 * Builds the corresponding uri (excluding protocol and host) by iterating
	 * through all configured routes and calling their respective resolves()
	 * method. If no matching route is found, an empty string is returned.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @throws \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
	 */
	public function resolve(array $routeValues) {
		$this->lastResolvedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->resolves($routeValues)) {
				$this->lastResolvedRoute = $route;
				return $route->getMatchingUri();
			}
		}
		$this->systemLogger->log('Router resolve(): Could not resolve a route for building an URI for the given route values.', LOG_WARNING, $routeValues);
		throw new NoMatchingRouteException('Could not resolve a route and its corresponding URI for the given parameters. This may be due to referring to a not existing package / controller / action while building a link or URI. Refer to log and check the backtrace for more details.', 1301610453);
	}

	/**
	 * Returns the route that has been resolved with the last resolve() call.
	 * Returns NULL if no route was found or resolve() has not been called yet
	 *
	 * @return Route
	 */
	public function getLastResolvedRoute() {
		return $this->lastResolvedRoute;
	}

	/**
	 * Creates TYPO3\Flow\Mvc\Routing\Route objects from the injected routes
	 * configuration.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException
	 */
	protected function createRoutesFromConfiguration() {
		if ($this->routesCreated === FALSE) {
			$this->routes = array();
			$routesWithHttpMethodConstraints = array();
			foreach ($this->routesConfiguration as $routeConfiguration) {
				$route = new Route();
				if (isset($routeConfiguration['name'])) {
					$route->setName($routeConfiguration['name']);
				}
				$uriPattern = $routeConfiguration['uriPattern'];
				$route->setUriPattern($uriPattern);
				if (isset($routeConfiguration['defaults'])) {
					$route->setDefaults($routeConfiguration['defaults']);
				}
				if (isset($routeConfiguration['routeParts'])) {
					$route->setRoutePartsConfiguration($routeConfiguration['routeParts']);
				}
				if (isset($routeConfiguration['toLowerCase'])) {
					$route->setLowerCase($routeConfiguration['toLowerCase']);
				}
				if (isset($routeConfiguration['appendExceedingArguments'])) {
					$route->setAppendExceedingArguments($routeConfiguration['appendExceedingArguments']);
				}
				if (isset($routeConfiguration['httpMethods'])) {
					if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === FALSE) {
						throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678427);
					}
					$routesWithHttpMethodConstraints[$uriPattern] = TRUE;
					$route->setHttpMethods($routeConfiguration['httpMethods']);
				} else {
					if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === TRUE) {
						throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678432);
					}
					$routesWithHttpMethodConstraints[$uriPattern] = FALSE;
				}
				$this->routes[] = $route;
			}
			$this->routesCreated = TRUE;
		}
	}

	/**
	 * Returns the object name of the controller defined by the package, subpackage key and
	 * controller name
	 *
	 * @param string $packageKey the package key of the controller
	 * @param string $subPackageKey the subpackage key of the controller
	 * @param string $controllerName the controller name excluding the "Controller" suffix
	 * @return string The controller's Object Name or NULL if the controller does not exist
	 * @api
	 */
	public function getControllerObjectName($packageKey, $subPackageKey, $controllerName) {
		$possibleObjectName = $this->controllerObjectNamePattern;
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $subPackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
		return ($controllerObjectName !== FALSE) ? $controllerObjectName : NULL;
	}
}
namespace TYPO3\Flow\Mvc\Routing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The default web router
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Router extends Router_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Router') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\Router', $this);
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Router') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\RouterInterface', $this);
		if ('TYPO3\Flow\Mvc\Routing\Router' === get_class($this)) {
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
			'findMatchResults' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', 'cacheMatchingCall', $objectManager, NULL),
				),
			),
			'resolve' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', 'cacheResolveCall', $objectManager, NULL),
				),
			),
			'route' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Fluid\Core\Widget\AjaxWidgetRoutingAspect', 'routeAjaxWidgetRequestAdvice', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Router') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\Router', $this);
		if (get_class($this) === 'TYPO3\Flow\Mvc\Routing\Router') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Routing\RouterInterface', $this);

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
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return array results of the matching route
	 */
	 protected function findMatchResults(\TYPO3\Flow\Http\Request $httpRequest) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['findMatchResults'])) {
		$result = parent::findMatchResults($httpRequest);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['findMatchResults'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['httpRequest'] = $httpRequest;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('findMatchResults');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Routing\Router', 'findMatchResults', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['findMatchResults']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['findMatchResults']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @throws \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
	 */
	 public function resolve(array $routeValues) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resolve'])) {
		$result = parent::resolve($routeValues);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['resolve'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['routeValues'] = $routeValues;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('resolve');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Routing\Router', 'resolve', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resolve']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['resolve']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Http\Request $httpRequest The web request to be analyzed. Will be modified by the router.
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	 public function route(\TYPO3\Flow\Http\Request $httpRequest) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['route'])) {
		$result = parent::route($httpRequest);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['route'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['httpRequest'] = $httpRequest;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('route');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Routing\Router', 'route', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['route']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['route']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\Router');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\Router', $propertyName, 'transient')) continue;
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