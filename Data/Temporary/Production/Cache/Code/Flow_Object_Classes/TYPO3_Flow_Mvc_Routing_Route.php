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
use TYPO3\Flow\Mvc\Exception\InvalidRoutePartHandlerException;
use TYPO3\Flow\Mvc\Exception\InvalidRoutePartValueException;
use TYPO3\Flow\Mvc\Exception\InvalidUriPatternException;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;

/**
 * Implementation of a standard route
 */
class Route_Original {

	const ROUTEPART_TYPE_STATIC = 'static';
	const ROUTEPART_TYPE_DYNAMIC = 'dynamic';
	const PATTERN_EXTRACTROUTEPARTS = '/(?P<optionalStart>\(?)(?P<dynamic>{?)(?P<content>@?[^}{\(\)]+)}?(?P<optionalEnd>\)?)/';

	/**
	 * Route name
	 *
	 * @var string
	 */
	protected $name = NULL;

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * URI Pattern of this route
	 *
	 * @var string
	 */
	protected $uriPattern = NULL;

 	/**
	 * Specifies whether Route Parts of this Route should be converted to lower case when resolved.
	 *
	 * @var boolean
	 */
	protected $lowerCase = TRUE;

	/**
	 * Specifies whether Route Values, that are not part of the Routes configuration, should be appended as query string
	 *
	 * @var boolean
	 */
	protected $appendExceedingArguments = FALSE;

	/**
	 * Contains the routing results (indexed by "package", "controller" and
	 * "action") after a successful call of matches()
	 *
	 * @var array
	 */
	protected $matchResults = array();

	/**
	 * Contains the matching uri (excluding protocol and host) after a
	 * successful call of resolves()
	 *
	 * @var string
	 */
	protected $matchingUri;

	/**
	 * Contains associative array of Route Part options
	 * (key: Route Part name, value: array of Route Part options)
	 *
	 * @var array
	 */
	protected $routePartsConfiguration = array();

	/**
	 * Container for Route Parts.
	 *
	 * @var array
	 */
	protected $routeParts = array();

	/**
	 * If not empty only the specified HTTP verbs are accepted by this route
	 *
	 * @var array non-associative array e.g. array('GET', 'POST')
	 */
	protected $httpMethods = array();

	/**
	 * Indicates whether this route is parsed.
	 * For better performance, routes are only parsed if needed.
	 *
	 * @var boolean
	 */
	protected $isParsed = FALSE;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Sets Route name.
	 *
	 * @param string $name The Route name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this Route.
	 *
	 * @return string Route name.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets default values for this Route.
	 * This array is merged with the actual matchResults when match() is called.
	 *
	 * @param array $defaults
	 * @return void
	 */
	public function setDefaults(array $defaults) {
		$this->defaults = $defaults;
	}

	/**
	 * Returns default values for this Route.
	 *
	 * @return array Route defaults
	 */
	public function getDefaults() {
		return $this->defaults;
	}

	/**
	 * Sets the URI pattern this route should match with
	 *
	 * @param string $uriPattern
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setUriPattern($uriPattern) {
		if (!is_string($uriPattern)) {
			throw new \InvalidArgumentException(sprintf('URI Pattern must be of type string, %s given.', gettype($uriPattern)), 1223499724);
		}
		$this->uriPattern = $uriPattern;
		$this->isParsed = FALSE;
	}

	/**
	 * Returns the URI pattern this route should match with
	 *
	 * @return string the URI pattern
	 */
	public function getUriPattern() {
		return $this->uriPattern;
	}

 	/**
	 * Specifies whether Route parts of this route should be converted to lower case when resolved.
	 * This setting can be overwritten for all dynamic Route parts.
	 *
	 * @param boolean $lowerCase TRUE: Route parts are converted to lower case by default. FALSE: Route parts are not altered.
	 * @return void
	 */
	public function setLowerCase($lowerCase) {
		$this->lowerCase = (boolean)$lowerCase;
	}

	/**
	 * Getter for $this->lowerCase.
	 *
	 * @return boolean TRUE if this Route part will be converted to lower case, otherwise FALSE.
	 * @see setLowerCase()
	 */
	public function isLowerCase() {
		return $this->lowerCase;
	}

	/**
	 * Specifies whether Route values, that are not part of the Route configuration, should be appended to the
	 * Resulting URI as query string.
	 * If set to FALSE, the route won't resolve if there are route values left after iterating through all Route Part
	 * handlers and removing the matching default values.
	 *
	 * @param boolean $appendExceedingArguments TRUE: exceeding arguments will be appended to the resulting URI
	 * @return void
	 */
	public function setAppendExceedingArguments($appendExceedingArguments) {
		$this->appendExceedingArguments = (boolean)$appendExceedingArguments;
	}

	/**
	 * Returns TRUE if exceeding arguments should be appended to the URI as query string, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function getAppendExceedingArguments() {
		return $this->appendExceedingArguments;
	}

	/**
	 * By default all Dynamic Route Parts are resolved by
	 * \TYPO3\Flow\Mvc\Routing\DynamicRoutePart.
	 * But you can specify different classes to handle particular Route Parts.
	 *
	 * Note: Route Part handlers must implement
	 * \TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface.
	 *
	 * Usage: setRoutePartsConfiguration(array('@controller' =>
	 *            array('handler' => 'TYPO3\Package\Subpackage\MyRoutePartHandler')));
	 *
	 * @param array $routePartsConfiguration Route Parts configuration options
	 * @return void
	 */
	public function setRoutePartsConfiguration(array $routePartsConfiguration) {
		$this->routePartsConfiguration = $routePartsConfiguration;
	}

	/**
	 * Returns the route parts configuration of this route
	 *
	 * @return array $routePartsConfiguration
	 */
	public function getRoutePartsConfiguration() {
		return $this->routePartsConfiguration;
	}

	/**
	 * Limits the HTTP verbs that are accepted by this route.
	 * If empty all HTTP verbs are accepted
	 *
	 * @param array $httpMethods non-associative array in the format array('GET', 'POST', ...)
	 * @return void
	 */
	public function setHttpMethods(array $httpMethods) {
		$this->httpMethods = $httpMethods;
	}

	/**
	 * @return array
	 */
	public function getHttpMethods() {
		return $this->httpMethods;
	}

	/**
	 * Whether or not this route is limited to one or more HTTP verbs
	 *
	 * @return boolean
	 */
	public function hasHttpMethodConstraints() {
		return $this->httpMethods !== array();
	}

	/**
	 * Returns an array with the Route match results.
	 *
	 * @return array An array of Route Parts and their values for further handling by the Router
	 * @see \TYPO3\Flow\Mvc\Routing\Router
	 */
	public function getMatchResults() {
		return $this->matchResults;
	}

	/**
	 * Returns the uri which corresponds to this Route.
	 *
	 * @return string A string containing the corresponding uri (excluding protocol and host)
	 */
	public function getMatchingUri() {
		return $this->matchingUri;
	}

	/**
	 * Checks whether $routePath corresponds to this Route.
	 * If all Route Parts match successfully TRUE is returned and
	 * $this->matchResults contains an array combining Route default values and
	 * calculated matchResults from the individual Route Parts.
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest the HTTP request to match
	 * @return boolean TRUE if this Route corresponds to the given $routePath, otherwise FALSE
	 * @throws InvalidRoutePartValueException
	 * @see getMatchResults()
	 */
	public function matches(Request $httpRequest) {
		$routePath = substr($httpRequest->getUri()->getPath(), strlen($httpRequest->getBaseUri()->getPath()));
		$this->matchResults = NULL;
		if ($routePath === NULL) {
			return FALSE;
		}
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}
		if ($this->hasHttpMethodConstraints() && (!in_array($httpRequest->getMethod(), $this->httpMethods))) {
			return FALSE;
		}
		$matchResults = array();

		$routePath = trim($routePath, '/');
		$skipOptionalParts = FALSE;
		$optionalPartCount = 0;
		/** @var $routePart RoutePartInterface */
		foreach ($this->routeParts as $routePart) {
			if ($routePart->isOptional()) {
				$optionalPartCount++;
				if ($skipOptionalParts) {
					if ($routePart->getDefaultValue() === NULL) {
						return FALSE;
					}
					continue;
				}
			} else {
				$optionalPartCount = 0;
				$skipOptionalParts = FALSE;
			}
			if ($routePart->match($routePath) !== TRUE) {
				if ($routePart->isOptional() && $optionalPartCount === 1) {
					if ($routePart->getDefaultValue() === NULL) {
						return FALSE;
					}
					$skipOptionalParts = TRUE;
				} else {
					return FALSE;
				}
			}
			$routePartValue = $routePart->getValue();
			if ($routePartValue !== NULL) {
				if ($this->containsObject($routePartValue)) {
					throw new InvalidRoutePartValueException('RoutePart::getValue() must only return simple types after calling RoutePart::match(). RoutePart "' . get_class($routePart) . '" returned one or more objects in Route "' . $this->getName() . '".');
				}
				$matchResults = Arrays::setValueByPath($matchResults, $routePart->getName(), $routePartValue);
			}
		}
		if (strlen($routePath) > 0) {
			return FALSE;
		}

		$this->matchResults = Arrays::arrayMergeRecursiveOverrule($this->defaults, $matchResults);
		return TRUE;
	}

	/**
	 * Checks whether $routeValues can be resolved to a corresponding uri.
	 * If all Route Parts can resolve one or more of the $routeValues, TRUE is
	 * returned and $this->matchingURI contains the generated URI (excluding
	 * protocol and host).
	 *
	 * @param array $routeValues An array containing key/value pairs to be resolved to uri segments
	 * @return boolean TRUE if this Route corresponds to the given $routeValues, otherwise FALSE
	 * @throws InvalidRoutePartValueException
	 * @see getMatchingUri()
	 */
	public function resolves(array $routeValues) {
		$this->matchingUri = NULL;
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}

		$matchingUri = '';
		$mergedRouteValues = Arrays::arrayMergeRecursiveOverrule($this->defaults, $routeValues);
		$remainingDefaults = $this->defaults;
		$requireOptionalRouteParts = FALSE;
		$matchingOptionalUriPortion = '';
		/** @var $routePart RoutePartInterface */
		foreach ($this->routeParts as $routePart) {
			if (!$routePart->resolve($routeValues)) {
				if (!$routePart->hasDefaultValue()) {
					return FALSE;
				}
			}
			if ($routePart->getName() !== NULL) {
				$remainingDefaults = Arrays::unsetValueByPath($remainingDefaults, $routePart->getName());
			}
			$routePartValue = NULL;
			if ($routePart->hasValue()) {
				$routePartValue = $routePart->getValue();
				if (!is_string($routePartValue)) {
					throw new InvalidRoutePartValueException('RoutePart::getValue() must return a string after calling RoutePart::resolve(), got ' . (is_object($routePartValue) ? get_class($routePartValue) : gettype($routePartValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
				}
			}
			$routePartDefaultValue = $routePart->getDefaultValue();
			if ($routePartDefaultValue !== NULL && !is_string($routePartDefaultValue)) {
				throw new InvalidRoutePartValueException('RoutePart::getDefaultValue() must return a string, got ' . (is_object($routePartDefaultValue) ? get_class($routePartDefaultValue) : gettype($routePartDefaultValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
			}
			if (!$routePart->isOptional()) {
				$matchingUri .= $routePart->hasValue() ? $routePartValue : $routePartDefaultValue;
				$requireOptionalRouteParts = FALSE;
				continue;
			}
			if ($routePart->hasValue() && strtolower($routePartValue) !== strtolower($routePartDefaultValue)) {
				$matchingOptionalUriPortion .= $routePartValue;
				$requireOptionalRouteParts = TRUE;
			} else {
				$matchingOptionalUriPortion .= $routePartDefaultValue;
			}
			if ($requireOptionalRouteParts) {
				$matchingUri .= $matchingOptionalUriPortion;
				$matchingOptionalUriPortion = '';
			}
		}

		if ($this->compareAndRemoveMatchingDefaultValues($remainingDefaults, $routeValues) !== TRUE) {
			return FALSE;
		}
		if (isset($routeValues['@format']) && $routeValues['@format'] === '') {
			unset($routeValues['@format']);
		}

		$this->throwExceptionIfTargetControllerDoesNotExist($mergedRouteValues);

			// add query string
		if (count($routeValues) > 0) {
			$routeValues = Arrays::removeEmptyElementsRecursively($routeValues);
			$routeValues = $this->persistenceManager->convertObjectsToIdentityArrays($routeValues);
			if (!$this->appendExceedingArguments) {
				$internalArguments = $this->extractInternalArguments($routeValues);
				if ($routeValues !== array()) {
					return FALSE;
				}
				$routeValues = $internalArguments;
			}
			$queryString = http_build_query($routeValues, NULL, '&');
			if ($queryString !== '') {
				$matchingUri .= strpos($matchingUri, '?') !== FALSE ? '&' . $queryString : '?' . $queryString;
			}
		}
		$this->matchingUri = $matchingUri;
		return TRUE;
	}

	/**
	 * Recursively iterates through the defaults of this route.
	 * If a route value is equal to a default value, it's removed
	 * from $routeValues.
	 * If a value exists but is not equal to is corresponding default,
	 * iteration is interrupted and FALSE is returned.
	 *
	 * @param array $defaults
	 * @param array $routeValues
	 * @return boolean FALSE if one of the $routeValues is not equal to it's default value. Otherwise TRUE
	 */
	protected function compareAndRemoveMatchingDefaultValues(array $defaults, array &$routeValues) {
		foreach ($defaults as $key => $defaultValue) {
			if (!isset($routeValues[$key])) {
				if ($defaultValue === '' || ($key === '@format' && strtolower($defaultValue) === 'html')) {
					continue;
				}
				return FALSE;
			}
			if (is_array($defaultValue)) {
				if (!is_array($routeValues[$key])) {
					return FALSE;
				}
				if ($this->compareAndRemoveMatchingDefaultValues($defaultValue, $routeValues[$key]) === FALSE) {
					return FALSE;
				}
				continue;
			} elseif (is_array($routeValues[$key])) {
				return FALSE;
			}
			if (strtolower($routeValues[$key]) !== strtolower($defaultValue)) {
				return FALSE;
			}
			unset($routeValues[$key]);
		}
		return TRUE;
	}

	/**
	 * Removes all internal arguments (prefixed with two underscores) from the given $arguments
	 * and returns them as array
	 *
	 * @param array $arguments
	 * @return array the internal arguments
	 */
	protected function extractInternalArguments(array &$arguments) {
		$internalArguments = array();
		foreach ($arguments as $argumentKey => &$argumentValue) {
			if (substr($argumentKey, 0, 2) === '__') {
				$internalArguments[$argumentKey] = $argumentValue;
				unset($arguments[$argumentKey]);
				continue;
			}
			if (substr($argumentKey, 0, 2) === '--' && is_array($argumentValue)) {
				$internalArguments[$argumentKey] = $this->extractInternalArguments($argumentValue);
				if ($internalArguments[$argumentKey] === array()) {
					unset($internalArguments[$argumentKey]);
				}
				if ($argumentValue === array()) {
					unset($arguments[$argumentKey]);
				}
			}
		}
		return $internalArguments;
	}

	/**
	 * Try to get the controller object name from the given $routeValues and throw an exception, if it can't be resolved.
	 *
	 * @param array $routeValues
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Routing\Exception\InvalidControllerException
	 */
	protected function throwExceptionIfTargetControllerDoesNotExist(array $routeValues) {
		$packageKey = isset($routeValues['@package']) ? $routeValues['@package'] : '';
		$subPackageKey = isset($routeValues['@subpackage']) ? $routeValues['@subpackage'] : '';
		$controllerName = isset($routeValues['@controller']) ? $routeValues['@controller'] : '';
		$controllerObjectName = $this->router->getControllerObjectName($packageKey, $subPackageKey, $controllerName);
		if ($controllerObjectName === NULL) {
			throw new Exception\InvalidControllerException('No controller object was found for package "' . $packageKey . '", subpackage "' . $subPackageKey . '", controller "' . $controllerName . '" in route "' . $this->getName() . '".', 1301650951);
		}
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
	 * Iterates through all segments in $this->uriPattern and creates
	 * appropriate RoutePart instances.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidRoutePartHandlerException
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
	 */
	public function parse() {
		if ($this->isParsed || $this->uriPattern === NULL || $this->uriPattern === '') {
			return;
		}
		$this->routeParts = array();
		$currentRoutePartIsOptional = FALSE;
		if (substr($this->uriPattern, -1) === '/') {
			throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" ends with a slash, which is not allowed. You can put the trailing slash in brackets to make it optional.', 1234782997);
		}
		if ($this->uriPattern[0] === '/') {
			throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" starts with a slash, which is not allowed.', 1234782983);
		}

		$matches = array();
		preg_match_all(self::PATTERN_EXTRACTROUTEPARTS, $this->uriPattern, $matches, PREG_SET_ORDER);

		/** @var $lastRoutePart RoutePartInterface */
		$lastRoutePart = NULL;
		foreach ($matches as $match) {
			$routePartType = empty($match['dynamic']) ? self::ROUTEPART_TYPE_STATIC : self::ROUTEPART_TYPE_DYNAMIC;
			$routePartName = $match['content'];
			if (!empty($match['optionalStart'])) {
				if ($lastRoutePart !== NULL && $lastRoutePart->isOptional()) {
					throw new InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive optional Route sections, which is not allowed.', 1234562050);
				}
				$currentRoutePartIsOptional = TRUE;
			}
			$routePart = NULL;
			switch ($routePartType) {
				case self::ROUTEPART_TYPE_DYNAMIC:
					if ($lastRoutePart instanceof DynamicRoutePartInterface) {
						throw new InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive Dynamic Route Parts, which is not allowed.', 1218446975);
					}
					if (isset($this->routePartsConfiguration[$routePartName]['handler'])) {
						$routePart = $this->objectManager->get($this->routePartsConfiguration[$routePartName]['handler']);
						if (!$routePart instanceof DynamicRoutePartInterface) {
							throw new InvalidRoutePartHandlerException('routePart handlers must implement "\TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface" in route "' . $this->getName() . '"', 1218480972);
						}
					} elseif (isset($this->routePartsConfiguration[$routePartName]['objectType'])) {
						$routePart = new IdentityRoutePart();
						$routePart->setObjectType($this->routePartsConfiguration[$routePartName]['objectType']);
						if (isset($this->routePartsConfiguration[$routePartName]['uriPattern'])) {
							$routePart->setUriPattern($this->routePartsConfiguration[$routePartName]['uriPattern']);
						}
					} else {
						$routePart = new DynamicRoutePart();
					}
					$routePartDefaultValue = ObjectAccess::getPropertyPath($this->defaults, $routePartName);
					if ($routePartDefaultValue !== NULL) {
						$routePart->setDefaultValue($routePartDefaultValue);
					}
					break;
				case self::ROUTEPART_TYPE_STATIC:
					$routePart = new StaticRoutePart();
					if ($lastRoutePart !== NULL && $lastRoutePart instanceof DynamicRoutePartInterface) {
						$lastRoutePart->setSplitString($routePartName);
					}
			}
			$routePart->setName($routePartName);
			$routePart->setOptional($currentRoutePartIsOptional);
			$routePart->setLowerCase($this->lowerCase);
			if (isset($this->routePartsConfiguration[$routePartName]['options'])) {
				$routePart->setOptions($this->routePartsConfiguration[$routePartName]['options']);
			}
			if (isset($this->routePartsConfiguration[$routePartName]['toLowerCase'])) {
				$routePart->setLowerCase($this->routePartsConfiguration[$routePartName]['toLowerCase']);
			}

			$this->routeParts[] = $routePart;
			if (!empty($match['optionalEnd'])) {
				if (!$currentRoutePartIsOptional) {
					throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unopened optional section.', 1234564495);
				}
				$currentRoutePartIsOptional = FALSE;
			}
			$lastRoutePart = $routePart;
		}
		if ($currentRoutePartIsOptional) {
			throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unterminated optional section.', 1234563922);
		}
		$this->isParsed = TRUE;
	}
}

namespace TYPO3\Flow\Mvc\Routing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Implementation of a standard route
 */
class Route extends Route_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Mvc\Routing\Route' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\Route');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\Route', $propertyName, 'transient')) continue;
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
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
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