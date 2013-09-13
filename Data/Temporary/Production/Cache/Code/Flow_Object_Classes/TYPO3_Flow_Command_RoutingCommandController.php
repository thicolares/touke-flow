<?php
namespace TYPO3\Flow\Command;

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

/**
 * Command controller for tasks related to routing
 *
 * @Flow\Scope("singleton")
 */
class RoutingCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * List the known routes
	 *
	 * This command displays a list of all currently registered routes.
	 *
	 * @return void
	 */
	public function listCommand() {
		$this->initializeRouter();

		$this->outputLine('Currently registered routes:');
		foreach ($this->router->getRoutes() as $index => $route) {
			$uriPattern = $route->getUriPattern();
			$this->outputLine(str_pad(($index + 1) . '. ' . $uriPattern, 80) . $route->getName());
		}
	}

	/**
	 * Show informations for a route
	 *
	 * This command displays the configuration of a route specified by index number.
	 *
	 * @param integer $index The index of the route as given by routing:list
	 * @return void
	 */
	public function showCommand($index) {
		$this->initializeRouter();

		$routes = $this->router->getRoutes();
		if (isset($routes[$index - 1])) {
			$route = $routes[$index - 1];

			$this->outputLine('<b>Information for route ' . $index . ':</b>');
			$this->outputLine('  Name: ' . $route->getName());
			$this->outputLine('  Pattern: ' . $route->getUriPattern());
			$this->outputLine('  Defaults: ');
			foreach ($route->getDefaults() as $defaultKey => $defaultValue) {
				$this->outputLine('    - ' . $defaultKey . ' => ' . $defaultValue);
			}
			$this->outputLine('  Append: ' . ($route->getAppendExceedingArguments() ? 'TRUE' : 'FALSE'));
		} else {
			$this->outputLine('Route ' . $index . ' was not found!');
		}
	}

	/**
	 * Generate a route path
	 *
	 * This command takes package, controller and action and displays the
	 * generated route path and the selected route:
	 *
	 * ./flow routing:getPath --format json Acme.Demo\\Sub\\Package
	 *
	 * @param string $package Package key and subpackage, subpackage parts are separated with backslashes
	 * @param string $controller Controller name, default is 'Standard'
	 * @param string $action Action name, default is 'index'
	 * @param string $format Requested Format name default is 'html'
	 * @return void
	 */
	public function getPathCommand($package, $controller = 'Standard', $action = 'index', $format = 'html') {
		$this->initializeRouter();

		$packageParts = explode('\\', $package, 2);
		$package = $packageParts[0];
		$subpackage = isset($packageParts[1]) ? $packageParts[1] : NULL;

		$routeValues = array(
			'@package' => $package,
			'@subpackage' => $subpackage,
			'@controller' => $controller,
			'@action' => $action,
			'@format' => $format
		);

		$this->outputLine('<b>Resolving:</b>');
		$this->outputLine('  Package: ' . $routeValues['@package']);
		$this->outputLine('  Subpackage: ' . $routeValues['@subpackage']);
		$this->outputLine('  Controller: ' . $routeValues['@controller']);
		$this->outputLine('  Action: ' . $routeValues['@action']);
		$this->outputLine('  Format: ' . $routeValues['@format']);

		foreach ($this->router->getRoutes() as $route) {
			try {
				$resolves = $route->resolves($routeValues);
				$controllerObjectName = $this->router->getControllerObjectName($package, $subpackage, $controller);
			} catch (\TYPO3\Flow\Mvc\Routing\Exception\InvalidControllerException $e) {
				$resolves = FALSE;
			}

			if ($resolves === TRUE) {
				$this->outputLine('<b>Route:</b>');
				$this->outputLine('  Name: ' . $route->getName());
				$this->outputLine('  Pattern: ' . $route->getUriPattern());

				$this->outputLine('<b>Generated Path:</b>');
				$this->outputLine('  ' . $route->getMatchingUri());

				if ($controllerObjectName !== NULL) {
					$this->outputLine('<b>Controller:</b>');
					$this->outputLine('  ' . $controllerObjectName);
				} else {
					$this->outputLine('<b>Controller Error:</b>');
					$this->outputLine('  !!! Controller Object was not found !!!');
				}
				return;
			}
		}
		$this->outputLine('<b>No Matching Controller found</b>');
	}

	/**
	 * Route the given route path
	 *
	 * This command takes a given path and displays the detected route and
	 * the selected package, controller and action.
	 *
	 * @param string $path The route path to resolve
	 * @param string $method The request method (GET, POST, PUT, DELETE, ...) to simulate
	 * @return void
	 */
	public function routePathCommand($path, $method = 'GET') {
		$this->initializeRouter();

		$server = array(
			'REQUEST_URI' => $path,
			'REQUEST_METHOD' => $method
		);
		$httpRequest = new Request(array(), array(), array(), $server);

		foreach ($this->router->getRoutes() as $route) {
			if ($route->matches($httpRequest) === TRUE) {

				$routeValues = $route->getMatchResults();

				$this->outputLine('<b>Path:</b>');
				$this->outputLine('  ' . $path);

				$this->outputLine('<b>Route:</b>');
				$this->outputLine('  Name: ' . $route->getName());
				$this->outputLine('  Pattern: ' . $route->getUriPattern());

				$this->outputLine('<b>Result:</b>');
				$this->outputLine('  Package: ' . (isset($routeValues['@package']) ? $routeValues['@package'] : '-'));
				$this->outputLine('  Subpackage: ' . (isset($routeValues['@subpackage']) ? $routeValues['@subpackage'] : '-'));
				$this->outputLine('  Controller: ' . (isset($routeValues['@controller']) ? $routeValues['@controller'] : '-'));
				$this->outputLine('  Action: ' . (isset($routeValues['@action']) ? $routeValues['@action'] : '-'));
				$this->outputLine('  Format: ' . (isset($routeValues['@format']) ? $routeValues['@format'] : '-'));

				$controllerObjectName = $this->router->getControllerObjectName($routeValues['@package'], (isset($routeValues['@subpackage']) ? $routeValues['@subpackage'] : NULL), $routeValues['@controller']);
				if ($controllerObjectName === NULL) {
					$this->outputLine('<b>Controller Error:</b>');
					$this->outputLine('  !!! No Controller Object found !!!');
					$this->quit(1);
				}
				$this->outputLine('<b>Controller:</b>');
				$this->outputLine('  ' . $controllerObjectName);
				$this->quit(0);
			}
		}
		$this->outputLine('No matching Route was found');
		$this->quit(1);
	}

	/**
	 * Initialize the injected router-object
	 *
	 * @return void
	 */
	protected function initializeRouter() {
		$routesConfiguration = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
	}
}
namespace TYPO3\Flow\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for tasks related to routing
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class RoutingCommandController extends RoutingCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Command\RoutingCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\RoutingCommandController', $this);
		parent::__construct();
		if ('TYPO3\Flow\Command\RoutingCommandController' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Command\RoutingCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\RoutingCommandController', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Command\RoutingCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Command\RoutingCommandController', $propertyName, 'transient')) continue;
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
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$configurationManager_reference = &$this->configurationManager;
		$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		if ($this->configurationManager === NULL) {
			$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('13edcae8fd67699bb78dadc8c1eac29c', $configurationManager_reference);
			if ($this->configurationManager === NULL) {
				$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('13edcae8fd67699bb78dadc8c1eac29c',  $configurationManager_reference, 'TYPO3\Flow\Configuration\ConfigurationManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager'); });
			}
		}
		$router_reference = &$this->router;
		$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Mvc\Routing\RouterInterface');
		if ($this->router === NULL) {
			$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('e2c83feb3f3f53acf88e4279c6c7a70d', $router_reference);
			if ($this->router === NULL) {
				$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('e2c83feb3f3f53acf88e4279c6c7a70d',  $router_reference, 'TYPO3\Flow\Mvc\Routing\Router', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Mvc\Routing\RouterInterface'); });
			}
		}
	}
}
#