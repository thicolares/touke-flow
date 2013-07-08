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
use TYPO3\Flow\Mvc\Controller\ControllerInterface;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Mvc\Exception\ForwardException;

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Dispatcher_Original {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Inject the Object Manager through setter injection because property injection
	 * is not available during compile time.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the Flow settings
	 *
	 * @param array $settings The Flow settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Dispatches a request to a controller
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request to dispatch
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InfiniteLoopException
	 * @api
	 */
	public function dispatch(RequestInterface $request, ResponseInterface $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) {
				throw new \TYPO3\Flow\Mvc\Exception\InfiniteLoopException('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			}
			$controller = $this->resolveController($request);
			try {
				$this->emitBeforeControllerInvocation($request, $response, $controller);
				$controller->processRequest($request, $response);
				$this->emitAfterControllerInvocation($request, $response, $controller);
			} catch (StopActionException $exception) {
				$this->emitAfterControllerInvocation($request, $response, $controller);
				if ($exception instanceof ForwardException) {
					$request = $exception->getNextRequest();
				} elseif ($request->isMainRequest() === FALSE) {
					$request = $request->getParentRequest();
				}
			}
		}
	}

	/**
	 * This signal is emitted directly before the request is been dispatched to a controller.
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitBeforeControllerInvocation(RequestInterface $request, ResponseInterface $response, ControllerInterface $controller) {
	}

	/**
	 * This signal is emitted directly after the request has been dispatched to a controller and the controller
	 * returned control back to the dispatcher.
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitAfterControllerInvocation(RequestInterface $request, ResponseInterface $response, ControllerInterface $controller) {
	}

	/**
	 * Finds and instantiates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request to dispatch
	 * @return \TYPO3\Flow\Mvc\Controller\ControllerInterface
	 * @throws \TYPO3\Flow\Configuration\Exception\NoSuchOptionException
	 * @throws \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException
	 */
	protected function resolveController(\TYPO3\Flow\Mvc\RequestInterface $request) {
		$controllerObjectName = $request->getControllerObjectName();
		if ($controllerObjectName === '') {
			if (isset($this->settings['mvc']['notFoundController'])) {
				throw new \TYPO3\Flow\Configuration\Exception\NoSuchOptionException('The configuration option TYPO3.Flow:mvc:notFoundController is deprecated since Flow 2.0. Use the "renderingGroups" option of the production exception handler instead in order to render custom error messages.', 1346949795);
			}
			$exceptionMessage = 'No controller could be resolved which would match your request';
			if ($request instanceof ActionRequest) {
				$exceptionMessage .= sprintf('. Package key: "%s", controller name: "%s"', $request->getControllerPackageKey(), $request->getControllerName());
				if ($request->getControllerSubpackageKey() !== NULL) {
					$exceptionMessage .= sprintf(', SubPackage key: "%s"', $request->getControllerSubpackageKey());
				}
			}
			throw new \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException($exceptionMessage, 1303209195, NULL, $request);
		}

		$controller = $this->objectManager->get($controllerObjectName);
		if (!$controller instanceof \TYPO3\Flow\Mvc\Controller\ControllerInterface) {
			throw new \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must be a valid request handling controller, ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1202921619, NULL, $request);
		}
		return $controller;
	}

}
namespace TYPO3\Flow\Mvc;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Dispatcher extends Dispatcher_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Mvc\Dispatcher') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Dispatcher', $this);
		if ('TYPO3\Flow\Mvc\Dispatcher' === get_class($this)) {
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
			'dispatch' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\RequestDispatchingAspect', 'blockIllegalRequestsAndForwardToAuthenticationEntryPoints', $objectManager, NULL),
				),
			),
			'emitBeforeControllerInvocation' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\SignalSlot\SignalAspect', 'forwardSignalToDispatcher', $objectManager, NULL),
				),
			),
			'emitAfterControllerInvocation' => array(
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
		if (get_class($this) === 'TYPO3\Flow\Mvc\Dispatcher') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Dispatcher', $this);

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
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request to dispatch
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InfiniteLoopException
	 */
	 public function dispatch(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['dispatch'])) {
		$result = parent::dispatch($request, $response);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['dispatch'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['request'] = $request;
				$methodArguments['response'] = $response;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('dispatch');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Dispatcher', 'dispatch', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['dispatch']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['dispatch']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitBeforeControllerInvocation(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response, \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitBeforeControllerInvocation'])) {
		$result = parent::emitBeforeControllerInvocation($request, $response, $controller);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitBeforeControllerInvocation'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['request'] = $request;
				$methodArguments['response'] = $response;
				$methodArguments['controller'] = $controller;
			
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Dispatcher', 'emitBeforeControllerInvocation', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitBeforeControllerInvocation']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Dispatcher', 'emitBeforeControllerInvocation', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitBeforeControllerInvocation']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitBeforeControllerInvocation']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitAfterControllerInvocation(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response, \TYPO3\Flow\Mvc\Controller\ControllerInterface $controller) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAfterControllerInvocation'])) {
		$result = parent::emitAfterControllerInvocation($request, $response, $controller);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAfterControllerInvocation'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['request'] = $request;
				$methodArguments['response'] = $response;
				$methodArguments['controller'] = $controller;
			
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Dispatcher', 'emitAfterControllerInvocation', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitAfterControllerInvocation']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Mvc\Dispatcher', 'emitAfterControllerInvocation', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAfterControllerInvocation']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAfterControllerInvocation']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Dispatcher');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Dispatcher', $propertyName, 'transient')) continue;
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
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
	}
}
#