<?php
namespace TYPO3\Flow\Security\Authentication;

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
use TYPO3\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Security\Exception;
use TYPO3\Flow\Security\RequestPatternResolver;

/**
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationProviderManager_Original implements AuthenticationManagerInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SecurityLoggerInterface
	 */
	protected $securityLogger;

	/**
	 * @var \TYPO3\Flow\Session\SessionInterface
	 * @Flow\Inject
	 */
	protected $session;

	/**
	 * The provider resolver
	 *
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver
	 */
	protected $providerResolver;

	/**
	 * The security context of the current request
	 *
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * The request pattern resolver
	 *
	 * @var \TYPO3\Flow\Security\RequestPatternResolver
	 */
	protected $requestPatternResolver;

	/**
	 * Array of \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface objects
	 *
	 * @var array
	 */
	protected $providers = array();

	/**
	 * Array of \TYPO3\Flow\Security\Authentication\TokenInterface objects
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * @var boolean
	 */
	protected $isAuthenticated = NULL;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver $providerResolver The provider resolver
	 * @param \TYPO3\Flow\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 */
	public function __construct(AuthenticationProviderResolver $providerResolver, RequestPatternResolver $requestPatternResolver) {
		$this->providerResolver = $providerResolver;
		$this->requestPatternResolver = $requestPatternResolver;
	}

	/**
	 * Inject the settings and does a fresh build of tokens based on the injected settings
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		if (!isset($settings['security']['authentication']['providers']) || !is_array($settings['security']['authentication']['providers'])) {
			return;
		}

		$this->buildProvidersAndTokensFromConfiguration($settings['security']['authentication']['providers']);
	}

	/**
	 * Sets the security context
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context of the current request
	 * @return void
	 */
	public function setSecurityContext(Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the security context
	 *
	 * @return \TYPO3\Flow\Security\Context $securityContext The security context of the current request
	 */
	public function getSecurityContext() {
		return $this->securityContext;
	}

	/**
	 * Returns clean tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
	 */
	public function getTokens() {
		return $this->tokens;
	}

	/**
	 * Tries to authenticate the tokens in the security context (in the given order)
	 * with the available authentication providers, if needed.
	 * If the authentication strategy is set to "allTokens", all tokens have to be authenticated.
	 * If the strategy is set to "oneToken", only one token needs to be authenticated, but the
	 * authentication will stop after the first authenticated token. The strategy
	 * "atLeastOne" will try to authenticate at least one and as many tokens as possible.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	public function authenticate() {
		$this->isAuthenticated = FALSE;
		$anyTokenAuthenticated = FALSE;

		if ($this->securityContext === NULL) {
			throw new Exception('Cannot authenticate because no security context has been set.', 1232978667);
		}

		$tokens = $this->securityContext->getAuthenticationTokens();
		if (count($tokens) === 0) {
			throw new AuthenticationRequiredException('The security context contained no tokens which could be authenticated.', 1258721059);
		}

		/** @var $token TokenInterface */
		foreach ($tokens as $token) {
			/** @var $provider \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface */
			foreach ($this->providers as $providerName => $provider) {
				if ($provider->canAuthenticate($token) && $token->getAuthenticationStatus() === TokenInterface::AUTHENTICATION_NEEDED) {
					$provider->authenticate($token);
					if ($token->isAuthenticated()) {
						$this->emitAuthenticatedToken($token);
					}
					break;
				}
			}
			if ($token->isAuthenticated()) {
				if (!$token instanceof SessionlessTokenInterface && !$this->session->isStarted()) {
					$this->session->start();
				}
				if ($this->securityContext->getAuthenticationStrategy() === Context::AUTHENTICATE_ONE_TOKEN) {
					$this->isAuthenticated = TRUE;
					return;
				}
				$anyTokenAuthenticated = TRUE;
			} else {
				if ($this->securityContext->getAuthenticationStrategy() === Context::AUTHENTICATE_ALL_TOKENS) {
					throw new AuthenticationRequiredException('Could not authenticate all tokens, but authenticationStrategy was set to "all".', 1222203912);
				}
			}
		}

		if (!$anyTokenAuthenticated && $this->securityContext->getAuthenticationStrategy() !== Context::AUTHENTICATE_ANY_TOKEN) {
			throw new AuthenticationRequiredException('Could not authenticate any token. Might be missing or wrong credentials or no authentication provider matched.', 1222204027);
		}

		$this->isAuthenticated = $anyTokenAuthenticated;
	}

	/**
	 * Checks if one or all tokens are authenticated (depending on the authentication strategy).
	 *
	 * Will call authenticate() if not done before.
	 *
	 * @return boolean
	 */
	public function isAuthenticated() {
		if ($this->isAuthenticated === NULL) {
			try {
				$this->authenticate();
			} catch(AuthenticationRequiredException $e) {}
		}
		return $this->isAuthenticated;
	}

	/**
	 * Logout all active authentication tokens
	 *
	 * @return void
	 */
	public function logout() {
		if ($this->isAuthenticated() !== TRUE) {
			return;
		}
		$this->isAuthenticated = NULL;
		/** @var $token TokenInterface */
		foreach ($this->securityContext->getAuthenticationTokens() as $token) {
			$token->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
		}
		$this->emitLoggedOut();
		if ($this->session->isStarted()) {
			$this->session->destroy('Logout through AuthenticationProviderManager');
		}
	}

	/**
	 * Signals that the specified token has been successfully authenticated.
	 *
	 * @param TokenInterface $token The token which has been authenticated
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitAuthenticatedToken(TokenInterface $token) {
	}

	/**
	 * Signals that all active authentication tokens have been invalidated.
	 * Note: the session will be destroyed after this signal has been emitted.
	 *
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitLoggedOut() {
	}

	/**
	 * Builds the provider and token objects based on the given configuration
	 *
	 * @param array $providerConfigurations The configured provider settings
	 * @return void
	 * @throws Exception\InvalidAuthenticationProviderException
	 * @throws Exception\NoEntryPointFoundException
	 */
	protected function buildProvidersAndTokensFromConfiguration(array $providerConfigurations) {
		foreach ($providerConfigurations as $providerName => $providerConfiguration) {

			if (isset($providerConfiguration['providerClass'])) {
				throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "providerClass". Check your settings and use the new option "provider" instead.', 1327672030);
			}
			if (isset($providerConfiguration['options'])) {
				throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" uses the deprecated option "options". Check your settings and use the new option "providerOptions" instead.', 1327672031);
			}
			if (!is_array($providerConfiguration) || !isset($providerConfiguration['provider'])) {
				throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerName . '" needs a "provider" option!', 1248209521);
			}

			$providerObjectName = $this->providerResolver->resolveProviderClass((string)$providerConfiguration['provider']);
			if ($providerObjectName === NULL) {
				throw new Exception\InvalidAuthenticationProviderException('The configured authentication provider "' . $providerConfiguration['provider'] . '" could not be found!', 1237330453);
			}
			$providerOptions = array();
			if (isset($providerConfiguration['providerOptions']) && is_array($providerConfiguration['providerOptions'])) {
				$providerOptions = $providerConfiguration['providerOptions'];
			}

			$providerInstance = new $providerObjectName($providerName, $providerOptions);
			$this->providers[$providerName] = $providerInstance;

			/** @var $tokenInstance TokenInterface */
			$tokenInstance = NULL;
			foreach ($providerInstance->getTokenClassNames() as $tokenClassName) {
				if (isset($providerConfiguration['token']) && $providerConfiguration['token'] !== $tokenClassName) {
					continue;
				}

				$tokenInstance = new $tokenClassName();
				$tokenInstance->setAuthenticationProviderName($providerName);
				$this->tokens[] = $tokenInstance;
				break;
			}

			if (isset($providerConfiguration['requestPatterns']) && is_array($providerConfiguration['requestPatterns'])) {
				$requestPatterns = array();
				foreach ($providerConfiguration['requestPatterns'] as $patternType => $patternConfiguration) {
					$patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);
					$requestPattern = new $patternClassName;
					$requestPattern->setPattern($patternConfiguration);
					$requestPatterns[] = $requestPattern;
				}
				if ($tokenInstance !== NULL) {
					$tokenInstance->setRequestPatterns($requestPatterns);
				}
			}

			if (isset($providerConfiguration['entryPoint'])) {
				if (is_array($providerConfiguration['entryPoint'])) {
					$message = 'Invalid entry point configuration in setting "TYPO3:Flow:security:authentication:providers:' . $providerName . '. Check your settings and make sure to specify only one entry point for each provider.';
					throw new Exception\InvalidAuthenticationProviderException($message, 1327671458);
				}
				$entryPointName = $providerConfiguration['entryPoint'];
				$entryPointClassName = $entryPointName;
				if (!class_exists($entryPointClassName)) {
					$entryPointClassName = 'TYPO3\Flow\Security\Authentication\EntryPoint\\' . $entryPointClassName;
				}
				if (!class_exists($entryPointClassName)) {
					throw new Exception\NoEntryPointFoundException('An entry point with the name: "' . $entryPointName . '" could not be resolved. Make sure it is a valid class name, either fully qualified or relative to TYPO3\Flow\Security\Authentication\EntryPoint!', 1236767282);
				}

				$entryPoint = new $entryPointClassName();
				if (isset($providerConfiguration['entryPointOptions'])) {
					$entryPoint->setOptions($providerConfiguration['entryPointOptions']);
				}

				$tokenInstance->setAuthenticationEntryPoint($entryPoint);
			}
		}
	}

}
namespace TYPO3\Flow\Security\Authentication;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The default authentication manager, which relies on Authentication Providers
 * to authenticate the tokens stored in the security context.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class AuthenticationProviderManager extends AuthenticationProviderManager_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver $providerResolver The provider resolver
	 * @param \TYPO3\Flow\Security\RequestPatternResolver $requestPatternResolver The request pattern resolver
	 */
	public function __construct() {
		$arguments = func_get_args();

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface', $this);

		if (!array_key_exists(0, $arguments)) $arguments[0] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver');
		if (!array_key_exists(1, $arguments)) $arguments[1] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\RequestPatternResolver');
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $providerResolver in class ' . __CLASS__ . '. Please check your calling code and Dependency Injection configuration.', 1296143787);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $requestPatternResolver in class ' . __CLASS__ . '. Please check your calling code and Dependency Injection configuration.', 1296143787);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager' === get_class($this)) {
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
			'authenticate' => array(
				'TYPO3\Flow\Aop\Advice\AfterAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterAdvice('TYPO3\Flow\Security\Aspect\LoggingAspect', 'logManagerAuthenticate', $objectManager, NULL),
				),
			),
			'logout' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\Security\Aspect\LoggingAspect', 'logManagerLogout', $objectManager, NULL),
				),
			),
			'emitAuthenticatedToken' => array(
				'TYPO3\Flow\Aop\Advice\AfterReturningAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice('TYPO3\Flow\SignalSlot\SignalAspect', 'forwardSignalToDispatcher', $objectManager, NULL),
				),
			),
			'emitLoggedOut' => array(
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
		if (get_class($this) === 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', $this);
		if (get_class($this) === 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface', $this);

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
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	 public function authenticate() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['authenticate'])) {
		$result = parent::authenticate();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['authenticate'] = TRUE;
			try {
			
					$methodArguments = array();

		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'authenticate', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['authenticate']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'authenticate', $joinPoint->getMethodArguments(), NULL, $result);
					$afterAdviceInvoked = TRUE;
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $exception) {

				if (!$afterAdviceInvoked) {
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['authenticate']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'authenticate', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['authenticate']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['authenticate']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 public function logout() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['logout'])) {
		$result = parent::logout();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['logout'] = TRUE;
			try {
			
					$methodArguments = array();

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'logout', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['logout']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'logout', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['logout']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['logout']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param TokenInterface $token The token which has been authenticated
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitAuthenticatedToken(\TYPO3\Flow\Security\Authentication\TokenInterface $token) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAuthenticatedToken'])) {
		$result = parent::emitAuthenticatedToken($token);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAuthenticatedToken'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['token'] = $token;
			
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'emitAuthenticatedToken', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitAuthenticatedToken']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'emitAuthenticatedToken', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAuthenticatedToken']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitAuthenticatedToken']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 * @\TYPO3\Flow\Annotations\Signal
	 */
	 protected function emitLoggedOut() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitLoggedOut'])) {
		$result = parent::emitLoggedOut();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['emitLoggedOut'] = TRUE;
			try {
			
					$methodArguments = array();

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'emitLoggedOut', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['emitLoggedOut']['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'emitLoggedOut', $joinPoint->getMethodArguments(), NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitLoggedOut']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['emitLoggedOut']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', $propertyName, 'transient')) continue;
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
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$securityLogger_reference = &$this->securityLogger;
		$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SecurityLoggerInterface');
		if ($this->securityLogger === NULL) {
			$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('cf5d8e4c29f4b5ca11e319496c806b88', $securityLogger_reference);
			if ($this->securityLogger === NULL) {
				$this->securityLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('cf5d8e4c29f4b5ca11e319496c806b88',  $securityLogger_reference, 'TYPO3\Flow\Log\Logger', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SecurityLoggerInterface'); });
			}
		}
		$session_reference = &$this->session;
		$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Session\SessionInterface');
		if ($this->session === NULL) {
			$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('3055dab6d586d9b0b7e34ad0e5d2b702', $session_reference);
			if ($this->session === NULL) {
				$this->session = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('3055dab6d586d9b0b7e34ad0e5d2b702',  $session_reference, 'TYPO3\Flow\Session\SessionInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Session\SessionInterface'); });
			}
		}
	}
}
#