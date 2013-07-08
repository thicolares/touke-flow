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

use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Annotations as Flow;

/**
 * An URI Builder
 *
 * @api
 */
class UriBuilder_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Arguments which have been used for building the last URI
	 * @var array
	 */
	protected $lastArguments = array();

	/**
	 * @var string
	 */
	protected $section = '';

	/**
	 * @var boolean
	 */
	protected $createAbsoluteUri = FALSE;

	/**
	 * @var boolean
	 */
	protected $addQueryString = FALSE;

	/**
	 * @var array
	 */
	protected $argumentsToBeExcludedFromQueryString = array();

	/**
	 * @var string
	 */
	protected $format = NULL;

	/**
	 * Sets the current request and resets the UriBuilder
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @return void
	 * @api
	 * @see reset()
	 */
	public function setRequest(\TYPO3\Flow\Mvc\ActionRequest $request) {
		$this->request = $request;
		$this->reset();
	}

	/**
	 * Gets the current request
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Additional query parameters.
	 * If you want to "prefix" arguments, you can pass in multidimensional arrays:
	 * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
	 *
	 * @param array $arguments
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * If specified, adds a given HTML anchor to the URI (#...)
	 *
	 * @param string $section
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setSection($section) {
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Specifies the format of the target (e.g. "html" or "xml")
	 *
	 * @param string $format (e.g. "html" or "xml"), will be transformed to lowercase!
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setFormat($format) {
		$this->format = strtolower($format);
		return $this;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * If set, the URI is prepended with the current base URI. Defaults to FALSE.
	 *
	 * @param boolean $createAbsoluteUri
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setCreateAbsoluteUri($createAbsoluteUri) {
		$this->createAbsoluteUri = (boolean)$createAbsoluteUri;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getCreateAbsoluteUri() {
		return $this->createAbsoluteUri;
	}

	/**
	 * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
	 *
	 * @param boolean $addQueryString
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setAddQueryString($addQueryString) {
		$this->addQueryString = (boolean)$addQueryString;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getAddQueryString() {
		return $this->addQueryString;
	}

	/**
	 * A list of arguments to be excluded from the query parameters
	 * Only active if addQueryString is set
	 *
	 * @param array $argumentsToBeExcludedFromQueryString
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString) {
		$this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 */
	public function getArgumentsToBeExcludedFromQueryString() {
		return $this->argumentsToBeExcludedFromQueryString;
	}

	/**
	 * Returns the arguments being used for the last URI being built.
	 * This is only set after build() / uriFor() has been called.
	 *
	 * @return array The last arguments
	 */
	public function getLastArguments() {
		return $this->lastArguments;
	}

	/**
	 * Resets all UriBuilder options to their default value.
	 * Note: This won't reset the Request that is attached to this UriBuilder (@see setRequest())
	 *
	 * @return \TYPO3\Flow\Mvc\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function reset() {
		$this->arguments = array();
		$this->section = '';
		$this->format = NULL;
		$this->createAbsoluteUri = FALSE;
		$this->addQueryString = FALSE;
		$this->argumentsToBeExcludedFromQueryString = array();

		return $this;
	}

	/**
	 * Creates an URI used for linking to an Controller action.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $controllerArguments Additional query parameters. Will be merged with $this->arguments.
	 * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
	 * @param string $packageKey Name of the target package. If not set, current Package is used.
	 * @param string $subPackageKey Name of the target SubPackage. If not set, current SubPackage is used.
	 * @return string the rendered URI
	 * @api
	 * @see build()
	 * @throws \TYPO3\Flow\Mvc\Routing\Exception\MissingActionNameException if $actionName parameter is empty
	 */
	public function uriFor($actionName, $controllerArguments = array(), $controllerName = NULL, $packageKey = NULL, $subPackageKey = NULL) {
		if ($actionName === NULL || $actionName === '') {
			throw new \TYPO3\Flow\Mvc\Routing\Exception\MissingActionNameException('The URI Builder could not build a URI linking to an action controller because no action name was specified. Please check the stack trace to see which code or template was requesting the link and check the arguments passed to the URI Builder.', 1354629891);
		}
		$controllerArguments['@action'] = strtolower($actionName);
		if ($controllerName !== NULL) {
			$controllerArguments['@controller'] = strtolower($controllerName);
		} else {
			$controllerArguments['@controller'] = strtolower($this->request->getControllerName());
		}
		if ($packageKey === NULL && $subPackageKey === NULL) {
			$subPackageKey = $this->request->getControllerSubpackageKey();
		}
		if ($packageKey === NULL) {
			$packageKey = $this->request->getControllerPackageKey();
		}
		$controllerArguments['@package'] = strtolower($packageKey);
		if ($subPackageKey !== NULL) {
			$controllerArguments['@subpackage'] = strtolower($subPackageKey);
		}
		if ($this->format !== NULL && $this->format !== '') {
			$controllerArguments['@format'] = $this->format;
		}

		$controllerArguments = $this->addNamespaceToArguments($controllerArguments, $this->request);
		return $this->build($controllerArguments);
	}

	/**
	 * Adds the argument namespace of the current request to the specified arguments.
	 * This happens recursively iterating through the nested requests in case of a subrequest.
	 * For example if this is executed inside a widget sub request in a plugin sub request, the result would be:
	 * array(
	 *   'pluginRequestNamespace' => array(
	 *     'widgetRequestNamespace => $arguments
	 *    )
	 * )
	 *
	 * @param array $arguments arguments
	 * @param \TYPO3\Flow\Mvc\RequestInterface $currentRequest
	 * @return array arguments with namespace
	 */
	protected function addNamespaceToArguments(array $arguments, \TYPO3\Flow\Mvc\RequestInterface $currentRequest) {
		while (!$currentRequest->isMainRequest()) {
			$argumentNamespace = $currentRequest->getArgumentNamespace();
			if ($argumentNamespace !== '') {
				$arguments = array($argumentNamespace => $arguments);
			}
			$currentRequest = $currentRequest->getParentRequest();
		}
		return $arguments;
	}

	/**
	 * Builds the URI
	 *
	 * @param array $arguments optional URI arguments. Will be merged with $this->arguments with precedence to $arguments
	 * @return string The URI
	 * @api
	 */
	public function build(array $arguments = array()) {
		$arguments = Arrays::arrayMergeRecursiveOverrule($this->arguments, $arguments);
		$arguments = $this->mergeArgumentsWithRequestArguments($arguments);

		$uri = $this->router->resolve($arguments);
		$this->lastArguments = $arguments;
		if (!$this->environment->isRewriteEnabled()) {
			$uri = 'index.php/' . $uri;
		}
		if ($this->createAbsoluteUri === TRUE) {
			$uri = $this->request->getHttpRequest()->getBaseUri() . $uri;
		}
		if ($this->section !== '') {
			$uri .= '#' . $this->section;
		}
		return $uri;
	}

	/**
	 * Merges specified arguments with arguments from request.
	 *
	 * If $this->request is no sub request, request arguments will only be merged if $this->addQueryString is set.
	 * Otherwise all request arguments except for the ones prefixed with the current request argument namespace will
	 * be merged. Additionally special arguments (PackageKey, SubpackageKey, ControllerName & Action) are merged.
	 *
	 * The argument provided through the $arguments parameter always overrule the request
	 * arguments.
	 *
	 * The request hierarchy is structured as follows:
	 * root (HTTP) > main (Action) > sub (Action) > sub sub (Action)
	 *
	 * @param array $arguments
	 * @return array
	 */
	protected function mergeArgumentsWithRequestArguments(array $arguments) {
		if ($this->request !== $this->request->getMainRequest()) {
			$subRequest = $this->request;
			while ($subRequest instanceof \TYPO3\Flow\Mvc\ActionRequest) {
				$requestArguments = (array)$subRequest->getArguments();

					// Reset arguments for the request that is bound to this UriBuilder instance
				if ($subRequest === $this->request) {
					if ($this->addQueryString === FALSE) {
						$requestArguments = array();
					} else {
						foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
							unset($requestArguments[$argumentToBeExcluded]);
						}
					}
				} else {
						// Remove all arguments of the current sub request if it's namespaced
					if ($this->request->getArgumentNamespace() !== '') {
						$requestNamespace = $this->getRequestNamespacePath($this->request);
						if ($this->addQueryString === FALSE) {
							$requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace);
						} else {
							foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
								$requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace . '.' . $argumentToBeExcluded);
							}
						}
					}
				}

					// Merge special arguments (package, subpackage, controller & action) from main request
				$requestPackageKey = $subRequest->getControllerPackageKey();
				if (!empty($requestPackageKey)) {
					$requestArguments['@package'] = $requestPackageKey;
				}
				$requestSubpackageKey = $subRequest->getControllerSubpackageKey();
				if (!empty($requestSubpackageKey)) {
					$requestArguments['@subpackage'] = $requestSubpackageKey;
				}
				$requestControllerName = $subRequest->getControllerName();
				if (!empty($requestControllerName)) {
					$requestArguments['@controller'] = $requestControllerName;
				}
				$requestActionName = $subRequest->getControllerActionName();
				if (!empty($requestActionName)) {
					$requestArguments['@action'] = $requestActionName;
				}

				if (count($requestArguments) > 0) {
					$requestArguments = $this->addNamespaceToArguments($requestArguments, $subRequest);
					$arguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
				}

				$subRequest = $subRequest->getParentRequest();
			}
		}

		if ($this->addQueryString === TRUE) {
			$requestArguments = $this->request->getArguments();
			foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
				unset($requestArguments[$argumentToBeExcluded]);
			}

			if (count($requestArguments) === 0) {
				return $arguments;
			}

			$arguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
		}

		return $arguments;
	}

	/**
	 * Get the path of the argument namespaces of all parent requests.
	 * Example: mainrequest.subrequest.subsubrequest
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @return string
	 */
	protected function getRequestNamespacePath($request) {
		if (!$request instanceof \TYPO3\Flow\Http\Request) {
			$parentPath = $this->getRequestNamespacePath($request->getParentRequest());
			return $parentPath . ($parentPath !== '' && $request->getArgumentNamespace() !== '' ? '.' : '') . $request->getArgumentNamespace();
		}
		return '';
	}

}
namespace TYPO3\Flow\Mvc\Routing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An URI Builder
 */
class UriBuilder extends UriBuilder_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Mvc\Routing\UriBuilder' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\UriBuilder');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\UriBuilder', $propertyName, 'transient')) continue;
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
		$router_reference = &$this->router;
		$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Mvc\Routing\RouterInterface');
		if ($this->router === NULL) {
			$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('e2c83feb3f3f53acf88e4279c6c7a70d', $router_reference);
			if ($this->router === NULL) {
				$this->router = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('e2c83feb3f3f53acf88e4279c6c7a70d',  $router_reference, 'TYPO3\Flow\Mvc\Routing\Router', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Mvc\Routing\RouterInterface'); });
			}
		}
		$environment_reference = &$this->environment;
		$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Utility\Environment');
		if ($this->environment === NULL) {
			$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('d7473831479e64d04a54de9aedcdc371', $environment_reference);
			if ($this->environment === NULL) {
				$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('d7473831479e64d04a54de9aedcdc371',  $environment_reference, 'TYPO3\Flow\Utility\Environment', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment'); });
			}
		}
	}
}
#