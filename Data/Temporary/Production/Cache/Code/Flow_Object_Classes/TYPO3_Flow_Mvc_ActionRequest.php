<?php
namespace TYPO3\Flow\Mvc;

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
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Request as HttpRequest;

/**
 * Represents an internal request targeted to a controller action
 *
 * @api
 */
class ActionRequest_Original implements RequestInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * Package key of the controller which is supposed to handle this request.
	 * @var string
	 */
	protected $controllerPackageKey = NULL;

	/**
	 * Subpackage key of the controller which is supposed to handle this request.
	 * @var string
	 */
	protected $controllerSubpackageKey = NULL;

	/**
	 * Object name of the controller which is supposed to handle this request.
	 * @var string
	 */
	protected $controllerName = NULL;

	/**
	 * Name of the action the controller is supposed to take.
	 * @var string
	 */
	protected $controllerActionName = NULL;

	/**
	 * The arguments for this request. They must be only simple types, no
	 * objects allowed.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Framework-internal arguments for this request, such as __referrer.
	 * All framework-internal arguments start with double underscore (__),
	 * and are only used from within the framework. Not for user consumption.
	 * Internal Arguments can be objects, in contrast to public arguments.
	 * @var array
	 */
	protected $internalArguments = array();

	/**
	 * Arguments and configuration for plugins – including widgets – which are
	 * sub controllers to the controller referred to by this request.
	 * @var array
	 */
	protected $pluginArguments = array();

	/**
	 * An optional namespace for arguments of this request. Used, for example, in
	 * plugins and widgets.
	 * @var string
	 */
	protected $argumentNamespace = '';

	/**
	 * The requested representation format
	 * @var string
	 */
	protected $format = NULL;

	/**
	 * If this request has been changed and needs to be dispatched again
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 * The parent request – either another ActionRequest or Http Request
	 * @var ActionRequest|HttpRequest
	 */
	protected $parentRequest;

	/**
	 * Cached pointer to the root request (usually an HTTP request)
	 * @var object
	 */
	protected $rootRequest;

	/**
	 * Cached pointer to a request referring to this one (if any)
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $referringRequest;

	/**
	 * Constructs this action request
	 *
	 * @param ActionRequest|HttpRequest $parentRequest Either an HTTP request or another ActionRequest
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function __construct($parentRequest) {
		if (!$parentRequest instanceof HttpRequest && !$parentRequest instanceof ActionRequest) {
			throw new \InvalidArgumentException('The parent request passed to ActionRequest::__construct() must be either an HTTP request or another ActionRequest', 1327846149);
		}
		$this->parentRequest = $parentRequest;
	}

	/**
	 * Returns the parent request
	 *
	 * @return ActionRequest|HttpRequest
	 * @api
	 */
	public function getParentRequest() {
		return $this->parentRequest;
	}

	/**
	 * Returns the top level request: the HTTP request object
	 *
	 * @return \TYPO3\Flow\Http\Request
	 * @api
	 */
	public function getHttpRequest() {
		if ($this->rootRequest === NULL) {
			$this->rootRequest = ($this->parentRequest instanceof HttpRequest) ? $this->parentRequest : $this->parentRequest->getHttpRequest();
		}
		return $this->rootRequest;
	}

	/**
	 * Returns the top level ActionRequest: the one just below the HTTP request
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 * @api
	 */
	public function getMainRequest() {
		return ($this->parentRequest instanceof HttpRequest) ? $this : $this->parentRequest->getMainRequest();
	}

	/**
	 * Checks if this request is the uppermost ActionRequest, just one below the
	 * HTTP request.
	 *
	 * @return boolean
	 * @api
	 */
	public function isMainRequest() {
		return ($this->parentRequest instanceof HttpRequest);
	}

	/**
	 * Returns an ActionRequest which referred to this request, if any.
	 *
	 * The referring request is not set or determined automatically but must be
	 * explicitly set through the corresponding internal argument "__referrer".
	 * This mechanism is used by Flow's form and validation mechanisms.
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest the referring request, or NULL if no referrer found
	 */
	public function getReferringRequest() {
		if ($this->referringRequest !== NULL) {
			return $this->referringRequest;
		}
		if (!isset($this->internalArguments['__referrer'])) {
			return NULL;
		}
		if (is_array($this->internalArguments['__referrer'])) {
			$referrerArray = $this->internalArguments['__referrer'];

			$referringRequest = $this->getHttpRequest()->createActionRequest();

			$arguments = array();
			if (isset($referrerArray['arguments'])) {
				$serializedArgumentsWithHmac = $referrerArray['arguments'];
				$serializedArguments = $this->hashService->validateAndStripHmac($serializedArgumentsWithHmac);
				$arguments = unserialize(base64_decode($serializedArguments));
				unset($referrerArray['arguments']);
			}

			$referringRequest->setArguments(\TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $referrerArray));
			return $referringRequest;
		} else {
			$this->referringRequest = $this->internalArguments['__referrer'];
		}
		return $this->referringRequest;
	}

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 * @api
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;

		if ($flag) {
			$this->emitRequestDispatched($this);
		}
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been dispatched successfully
	 * @api
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @api
	 */
	public function getControllerObjectName() {
		$possibleObjectName = '@package\@subpackage\Controller\@controllerController';
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $this->controllerPackageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $this->controllerSubpackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $this->controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
		return ($controllerObjectName !== FALSE) ? $controllerObjectName : '';
	}

	/**
	 * Explicitly sets the object name of the controller
	 *
	 * @param string $unknownCasedControllerObjectName The fully qualified controller object name
	 * @return void
	 * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
	 * @api
	 */
	public function setControllerObjectName($unknownCasedControllerObjectName) {
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($unknownCasedControllerObjectName);

		if ($controllerObjectName === FALSE) {
			throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object "' . $unknownCasedControllerObjectName . '" is not registered.', 1268844071);
		}

		$this->controllerPackageKey = $this->objectManager->getPackageKeyByObjectName($controllerObjectName);

		$matches = array();
		$subject = substr($controllerObjectName, strlen($this->controllerPackageKey) + 1);
		preg_match('/
			^(
				Controller
			|
				(?P<subpackageKey>.+)\\\\Controller
			)
			\\\\(?P<controllerName>[a-z\\\\]+)Controller
			$/ix', $subject, $matches
		);

		$this->controllerSubpackageKey = (isset($matches['subpackageKey'])) ? $matches['subpackageKey'] : NULL;
		$this->controllerName = $matches['controllerName'];
	}

	/**
	 * Sets the package key of the controller.
	 *
	 * This function tries to determine the correct case for the given package key.
	 * If the Package Manager does not know the specified package, the package key
	 * cannot be verified or corrected and is stored as is.
	 *
	 * @param string $packageKey The package key
	 * @return void
	 * @api
	 */
	public function setControllerPackageKey($packageKey) {
		$correctlyCasedPackageKey = $this->packageManager->getCaseSensitivePackageKey($packageKey);
		$this->controllerPackageKey = ($correctlyCasedPackageKey !== FALSE) ? $correctlyCasedPackageKey : $packageKey;
	}

	/**
	 * Returns the package key of the specified controller.
	 *
	 * @return string The package key
	 * @api
	 */
	public function getControllerPackageKey() {
		return $this->controllerPackageKey;
	}

	/**
	 * Sets the subpackage key of the controller.
	 *
	 * @param string $subpackageKey The subpackage key.
	 * @return void
	 */
	public function setControllerSubpackageKey($subpackageKey) {
		$this->controllerSubpackageKey = (empty($subpackageKey) ? NULL : $subpackageKey);
	}

	/**
	 * Returns the subpackage key of the specified controller.
	 * If there is no subpackage key set, the method returns NULL.
	 *
	 * @return string The subpackage key
	 * @api
	 */
	public function getControllerSubpackageKey() {
		return $this->controllerSubpackageKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * Examples: "Standard", "Account", ...
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidControllerNameException
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		}
		if (strpos($controllerName, '_') !== FALSE) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidControllerNameException('The controller name must not contain underscores.', 1217846412);
		}
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Name of the controller
	 * @api
	 */
	public function getControllerName() {
		$controllerObjectName = $this->getControllerObjectName();
		if ($controllerObjectName !== '')  {

				// Extract the controller name from the controller object name to assure that
				// the case is correct.
				// Note: Controller name can also contain sub structure like "Foo\Bar\Baz"
			return substr($controllerObjectName, -(strlen($this->controllerName)+10), -10);
		} else {
			return $this->controllerName;
		}
	}

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $actionName Name of the action to execute by the controller
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidActionNameException if the action name is not valid
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		}
		if ($actionName === '') {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidActionNameException('The action name must not be an empty string.', 1289472991);
		}
		if ($actionName[0] !== strtolower($actionName[0])) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		}
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @api
	 */
	public function getControllerActionName() {
		$controllerObjectName = $this->getControllerObjectName();
		if ($controllerObjectName !== '' && ($this->controllerActionName === strtolower($this->controllerActionName)))  {
			$controllerClassName = $this->objectManager->getClassNameByObjectName($controllerObjectName);
			$lowercaseActionMethodName = strtolower($this->controllerActionName) . 'action';
			foreach (get_class_methods($controllerClassName) as $existingMethodName) {
				if (strtolower($existingMethodName) === $lowercaseActionMethodName) {
					$this->controllerActionName = substr($existingMethodName, 0, -6);
					break;
				}
			}
		}
		return $this->controllerActionName;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException if the given argument name is no string
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException if the given argument value is an object
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) === 0) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException('Invalid argument name (must be a non-empty string).', 1210858767);
		}

		if (substr($argumentName, 0, 2) === '__') {
			$this->internalArguments[$argumentName] = $value;
			return;
		}

		if (is_object($value)) {
			throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException('You are not allowed to store objects in the request arguments. Please convert the object of type "' . get_class($value) . '" given for argument "' . $argumentName . '" to a simple type first.', 1302783022);
		}

		if (substr($argumentName, 0, 2) === '--') {
			$this->pluginArguments[substr($argumentName, 2)] = $value;
			return;
		}

		switch ($argumentName) {
			case '@package':
				$this->setControllerPackageKey($value);
				break;
			case '@subpackage':
				$this->setControllerSubpackageKey($value);
				break;
			case '@controller':
				$this->setControllerName($value);
				break;
			case '@action':
				$this->setControllerActionName($value);
				break;
			case '@format':
				$this->setFormat($value);
				break;
			default:
				$this->arguments[$argumentName] = $value;
		}

	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @throws \TYPO3\Flow\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \TYPO3\Flow\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Sets the specified arguments.
	 *
	 * The arguments array will be reset therefore any arguments
	 * which existed before will be overwritten!
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException if an argument name is no string
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException if an argument value is an object
	 */
	public function setArguments(array $arguments) {
		$this->arguments = array();
		foreach ($arguments as $key => $value) {
			$this->setArgument($key, $value);
		}
	}

	/**
	 * Returns an Array of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the value of the specified internal argument.
	 *
	 * Internal arguments are set via setArgument(). In order to be handled as an
	 * internal argument, its name must start with two underscores.
	 *
	 * @param string $argumentName Name of the argument, for example "__fooBar"
	 * @return string Value of the argument, or NULL if not set.
	 */
	public function getInternalArgument($argumentName) {
		return (isset($this->internalArguments[$argumentName]) ? $this->internalArguments[$argumentName] : NULL);
	}

	/**
	 * Returns the internal arguments of the request, that is, all arguments whose
	 * name starts with two underscores.
	 *
	 * @return array
	 */
	public function getInternalArguments() {
		return $this->internalArguments;
	}

	/**
	 * Sets a namespace for the arguments of this request.
	 *
	 * This doesn't affect the actual behavior of argument handling within this
	 * classes' methods but is used in other parts of Flow and its libraries to
	 * render argument names which don't conflict with each other.
	 *
	 * @param string $namespace Argument namespace
	 * @return void
	 */
	public function setArgumentNamespace($namespace) {
		$this->argumentNamespace = $namespace;
	}

	/**
	 * Returns the argument namespace, if any.
	 *
	 * @return string
	 */
	public function getArgumentNamespace() {
		return $this->argumentNamespace;
	}

	/**
	 * Returns an array of plugin argument configurations
	 *
	 * @return array
	 */
	public function getPluginArguments() {
		return $this->pluginArguments;
	}

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like. Can even be something like "rss.xml".
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = strtolower($format);
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Emits a signal when a Request has been dispatched
	 *
	 * The action request is not proxyable, so the signal is dispatched manually here.
	 * The safeguard allows unit tests without the dispatcher dependency.
	 *
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitRequestDispatched($request) {
		if ($this->objectManager !== NULL) {
			$dispatcher = $this->objectManager->get('TYPO3\Flow\SignalSlot\Dispatcher');
			if ($dispatcher !== NULL) {
				$dispatcher->dispatch('TYPO3\Flow\Mvc\ActionRequest', 'requestDispatched', array($request));
			}
		}
	}

	/**
	 * Resets the dispatched status to FALSE
	 */
	public function __clone() {
		$this->dispatched = FALSE;
	}

}
namespace TYPO3\Flow\Mvc;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Represents an internal request targeted to a controller action
 */
class ActionRequest extends ActionRequest_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 * @param ActionRequest|HttpRequest $parentRequest Either an HTTP request or another ActionRequest
	 * @throws \InvalidArgumentException
	 */
	public function __construct() {
		$arguments = func_get_args();

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $parentRequest in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Mvc\ActionRequest' === get_class($this)) {
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
			'emitRequestDispatched' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\SignalSlot\SignalAspect', 'forwardSignalToDispatcher', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
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
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitRequestDispatched($request) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitRequestDispatched'])) {
		$result = parent::emitRequestDispatched($request);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitRequestDispatched'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['request'] = $request;
			
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\ActionRequest', 'emitRequestDispatched', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitRequestDispatched']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\ActionRequest', 'emitRequestDispatched', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitRequestDispatched']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitRequestDispatched']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\ActionRequest');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\ActionRequest', $propertyName, 'transient')) continue;
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
		$hashService_reference = &$this->hashService;
		$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Cryptography\HashService');
		if ($this->hashService === NULL) {
			$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('af606f3838da2ad86bf0ed2ff61be394', $hashService_reference);
			if ($this->hashService === NULL) {
				$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('af606f3838da2ad86bf0ed2ff61be394',  $hashService_reference, 'TYPO3\Flow\Security\Cryptography\HashService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Cryptography\HashService'); });
			}
		}
		$packageManager_reference = &$this->packageManager;
		$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Package\PackageManagerInterface');
		if ($this->packageManager === NULL) {
			$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('aad0cdb65adb124cf4b4d16c5b42256c', $packageManager_reference);
			if ($this->packageManager === NULL) {
				$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('aad0cdb65adb124cf4b4d16c5b42256c',  $packageManager_reference, 'TYPO3\Flow\Package\PackageManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'); });
			}
		}
	}
}
#