<?php
namespace TYPO3\Flow\Security;

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
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Utility\Algorithms;

/**
 * This is the default implementation of a security context, which holds current
 * security information like roles oder details of authenticated users.
 *
 * @Flow\Scope("session")
 */
class Context_Original {

	/**
	 * Authenticate as many tokens as possible but do not require
	 * an authenticated token (e.g. for guest users with role Everybody).
	 */
	const AUTHENTICATE_ANY_TOKEN = 1;

	/**
	 * Stop authentication of tokens after first successful
	 * authentication of a token.
	 */
	const AUTHENTICATE_ONE_TOKEN = 2;

	/**
	 * Authenticate all active tokens and throw an exception if
	 * an active token could not be authenticated.
	 */
	const AUTHENTICATE_ALL_TOKENS = 3;

	/**
	 * Authenticate as many tokens as possible but do not fail if
	 * a token could not be authenticated and at least one token
	 * could be authenticated.
	 */
	const AUTHENTICATE_AT_LEAST_ONE_TOKEN = 4;

	/**
	 * Creates one csrf token per session
	 */
	const CSRF_ONE_PER_SESSION = 1;

	/**
	 * Creates one csrf token per uri
	 */
	const CSRF_ONE_PER_URI = 2;

	/**
	 * Creates one csrf token per request
	 */
	const CSRF_ONE_PER_REQUEST = 3;

	/**
	 * TRUE if the context is initialized in the current request, FALSE or NULL otherwise.
	 *
	 * @var boolean
	 * @Flow\Transient
	 */
	protected $initialized = FALSE;

	/**
	 * Array of configured tokens (might have request patterns)
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Array of tokens currently active
	 * @var array
	 * @Flow\Transient
	 */
	protected $activeTokens = array();

	/**
	 * Array of tokens currently inactive
	 * @var array
	 * @Flow\Transient
	 */
	protected $inactiveTokens = array();

	/**
	 * One of the AUTHENTICATE_* constants to set the authentication strategy.
	 * @var integer
	 */
	protected $authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 * @Flow\Transient
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * One of the CSRF_* constants to set the csrf protection strategy
	 * @var integer
	 */
	protected $csrfProtectionStrategy = self::CSRF_ONE_PER_SESSION;

	/**
	 * @var array
	 */
	protected $csrfProtectionTokens = array();

	/**
	 * @var \TYPO3\Flow\Mvc\RequestInterface
	 */
	protected $interceptedRequest;

	/**
	 * @Flow\Transient
	 * @var array<\TYPO3\Flow\Security\Policy\Role>
	 */
	protected $roles = NULL;

	/**
	 * Whether authorization is disabled @see areAuthorizationChecksDisabled()
	 * @Flow\Transient
	 * @var boolean
	 */
	protected $authorizationChecksDisabled = FALSE;

	/**
	 * Inject the authentication manager
	 *
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager
	 * @return void
	 */
	public function injectAuthenticationManager(Authentication\AuthenticationManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
		$this->authenticationManager->setSecurityContext($this);
	}

	/**
	 * Lets you switch off authorization checks (CSRF token, policies, content security, ...) for the runtime of $callback
	 *
	 * Usage:
	 * $this->securityContext->withoutAuthorizationChecks(function ($accountRepository, $username, $providerName, &$account) {
	 *   // this will disable the PersistenceQueryRewritingAspect for this one call
	 *   $account = $accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($username, $providerName)
	 * });
	 *
	 * @param \Closure $callback
	 * @return void
	 * @throws \Exception
	 */
	public function withoutAuthorizationChecks(\Closure $callback) {
		$this->authorizationChecksDisabled = TRUE;
		try {
			$callback->__invoke();
		} catch (\Exception $exception) {
			$this->authorizationChecksDisabled = FALSE;
			throw $exception;
		}
		$this->authorizationChecksDisabled = FALSE;
	}

	/**
	 * Returns TRUE if authorization should be ignored, otherwise FALSE
	 * This is mainly useful to fetch records without Content Security to kick in (e.g. for AuthenticationProviders)
	 *
	 * @return boolean
	 * @see withoutAuthorizationChecks()
	 */
	public function areAuthorizationChecksDisabled() {
		return $this->authorizationChecksDisabled;
	}

	/**
	 * Set the current action request
	 *
	 * This method is called manually by the request handler which created the HTTP
	 * request.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The current ActionRequest
	 * @return void
	 * @Flow\Autowiring(FALSE)
	 */
	public function setRequest(ActionRequest $request) {
		$this->request = $request;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['authentication']['authenticationStrategy'])) {
			$authenticationStrategyName = $settings['security']['authentication']['authenticationStrategy'];
			switch ($authenticationStrategyName) {
				case 'allTokens':
					$this->authenticationStrategy = self::AUTHENTICATE_ALL_TOKENS;
					break;
				case 'oneToken':
					$this->authenticationStrategy = self::AUTHENTICATE_ONE_TOKEN;
					break;
				case 'atLeastOneToken':
					$this->authenticationStrategy = self::AUTHENTICATE_AT_LEAST_ONE_TOKEN;
					break;
				case 'anyToken':
					$this->authenticationStrategy = self::AUTHENTICATE_ANY_TOKEN;
					break;
				default:
					throw new \TYPO3\Flow\Exception('Invalid setting "' . $authenticationStrategyName . '" for security.authentication.authenticationStrategy', 1291043022);
			}
		}

		if (isset($settings['security']['csrf']['csrfStrategy'])) {
			$csrfStrategyName = $settings['security']['csrf']['csrfStrategy'];
			switch ($csrfStrategyName) {
				case 'onePerRequest':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_REQUEST;
					break;
				case 'onePerSession':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_SESSION;
					break;
				case 'onePerUri':
					$this->csrfProtectionStrategy = self::CSRF_ONE_PER_URI;
					break;
				default:
					throw new \TYPO3\Flow\Exception('Invalid setting "' . $csrfStrategyName . '" for security.csrf.csrfStrategy', 1291043024);
			}
		}
	}

	/**
	 * Initializes the security context for the given request.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	public function initialize() {
		if ($this->initialized === TRUE) {
			return;
		}
		if ($this->canBeInitialized() === FALSE) {
			throw new \TYPO3\Flow\Exception('The security Context cannot be initialized yet. Please check if it can be initialized with $securityContext->canBeInitialized() before trying to do so.', 1358513802);
		}

		if ($this->csrfProtectionStrategy !== self::CSRF_ONE_PER_SESSION) {
			$this->csrfProtectionTokens = array();
		}

		$this->tokens = $this->mergeTokens($this->authenticationManager->getTokens(), $this->tokens);
		$this->separateActiveAndInactiveTokens();
		$this->updateTokens($this->activeTokens);

		$this->initialized = TRUE;
	}

	/**
	 * @return boolean TRUE if the Context is initialized, FALSE otherwise.
	 */
	public function isInitialized() {
		return $this->initialized;
	}

	/**
	 * Get the token authentication strategy
	 *
	 * @return int One of the AUTHENTICATE_* constants
	 */
	public function getAuthenticationStrategy() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		return $this->authenticationStrategy;
	}

	/**
	 * Returns all \TYPO3\Flow\Security\Authentication\Tokens of the security context which are
	 * active for the current request. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens
	 */
	public function getAuthenticationTokens() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		return $this->activeTokens;
	}

	/**
	 * Returns all \TYPO3\Flow\Security\Authentication\Tokens of the security context which are
	 * active for the current request and of the given type. If a token has a request pattern that cannot match
	 * against the current request it is determined as not active.
	 *
	 * @param string $className The class name
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens of the specified type
	 */
	public function getAuthenticationTokensOfType($className) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		$activeTokens = array();
		foreach ($this->activeTokens as $token) {
			if ($token instanceof $className) {
				$activeTokens[] = $token;
			}
		}

		return $activeTokens;
	}

	/**
	 * Returns the roles of all authenticated accounts, including inherited roles.
	 *
	 * If no authenticated roles could be found the "Anonymous" role is returned.
	 *
	 * The "Everybody" roles is always returned.
	 *
	 * @return array<\TYPO3\Flow\Security\Policy\Role>
	 */
	public function getRoles() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if ($this->roles === NULL) {
			$this->roles = array('Everybody' => $this->policyService->getRole('Everybody'));

			if ($this->authenticationManager->isAuthenticated() === FALSE) {
				$this->roles['Anonymous'] = $this->policyService->getRole('Anonymous');
			} else {
				foreach ($this->getAuthenticationTokens() as $token) {
					if ($token->isAuthenticated() === TRUE) {
						$account = $token->getAccount();
						if ($account !== NULL) {
							$accountRoles = $account->getRoles();
							/** @var $currentRole Role */
							foreach ($accountRoles as $currentRole) {
								if (!in_array($currentRole, $this->roles)) {
									$this->roles[$currentRole->getIdentifier()] = $currentRole;
								}
								/** @var $currentParentRole Role */
								foreach ($this->policyService->getAllParentRoles($currentRole) as $currentParentRole) {
									if (!in_array($currentParentRole, $this->roles)) {
										$this->roles[$currentParentRole->getIdentifier()] = $currentParentRole;
									}
								}
							}
						}
					}
				}
			}
		}

		return $this->roles;
	}

	/**
	 * Returns TRUE, if at least one of the currently authenticated accounts holds
	 * a role with the given identifier, also recursively.
	 *
	 * @param string $roleIdentifier The string representation of the role to search for
	 * @return boolean TRUE, if a role with the given string representation was found
	 */
	public function hasRole($roleIdentifier) {
		if ($roleIdentifier === 'Everybody') {
			return TRUE;
		}
		if ($roleIdentifier === 'Anonymous') {
			return (!$this->authenticationManager->isAuthenticated());
		}

		$roles = $this->getRoles();
		return isset($roles[$roleIdentifier]);
	}

	/**
	 * Returns the party of the first authenticated authentication token.
	 * Note: There might be a different party authenticated in one of the later tokens,
	 * if you need it you'll have to fetch it directly from the token.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	public function getParty() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) {
				return $token->getAccount() !== NULL ? $token->getAccount()->getParty() : NULL;
			}
		}
		return NULL;
	}

	/**
	 * Returns the first authenticated party of the given type.
	 *
	 * @param string $className Class name of the party to find
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	public function getPartyByType($className) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE && $token->getAccount()->getParty() instanceof $className) {
				return $token->getAccount()->getParty();
			}
		}
		return NULL;
	}

	/**
	 * Returns the account of the first authenticated authentication token.
	 * Note: There might be a more currently authenticated account in the
	 * remaining tokens. If you need them you'll have to fetch them directly
	 * from the tokens.
	 * (@see getAuthenticationTokens())
	 *
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	public function getAccount() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		foreach ($this->getAuthenticationTokens() as $token) {
			if ($token->isAuthenticated() === TRUE) {
				return $token->getAccount();
			}
		}
		return NULL;
	}

	/**
	 * Returns an authenticated account for the given provider or NULL if no
	 * account was authenticated or no token was registered for the given
	 * authentication provider name.
	 *
	 * @param string $authenticationProviderName Authentication provider name of the account to find
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	public function getAccountByAuthenticationProviderName($authenticationProviderName) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (isset($this->activeTokens[$authenticationProviderName]) && $this->activeTokens[$authenticationProviderName]->isAuthenticated() === TRUE) {
			return $this->activeTokens[$authenticationProviderName]->getAccount();
		}
		return NULL;
	}

	/**
	 * Returns the current CSRF protection token. A new one is created when needed, depending on the  configured CSRF
	 * protection strategy.
	 *
	 * @return string
	 */
	public function getCsrfProtectionToken() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (count($this->csrfProtectionTokens) === 1 && $this->csrfProtectionStrategy !== self::CSRF_ONE_PER_URI) {
			reset($this->csrfProtectionTokens);
			return key($this->csrfProtectionTokens);
		}
		$newToken = Algorithms::generateRandomToken(16);
		$this->csrfProtectionTokens[$newToken] = TRUE;

		return $newToken;
	}

	/**
	 * Returns TRUE if the context has CSRF protection tokens.
	 *
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	public function hasCsrfProtectionTokens() {
		return count($this->csrfProtectionTokens) > 0;
	}

	/**
	 * Returns TRUE if the given string is a valid CSRF protection token. The token will be removed if the configured
	 * csrf strategy is 'onePerUri'.
	 *
	 * @param string $csrfToken The token string to be validated
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	public function isCsrfProtectionTokenValid($csrfToken) {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		if (isset($this->csrfProtectionTokens[$csrfToken])) {
			if ($this->csrfProtectionStrategy === self::CSRF_ONE_PER_URI) {
				unset($this->csrfProtectionTokens[$csrfToken]);
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Sets an action request, to be stored for later resuming after it
	 * has been intercepted by a security exception.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $interceptedRequest
	 * @return void
	 * @Flow\Session(autoStart=true)
	 */
	public function setInterceptedRequest(ActionRequest $interceptedRequest = NULL) {
		$this->interceptedRequest = $interceptedRequest;
	}

	/**
	 * Returns the request, that has been stored for later resuming after it
	 * has been intercepted by a security exception, NULL if there is none.
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function getInterceptedRequest() {
		return $this->interceptedRequest;
	}

	/**
	 * Clears the security context.
	 *
	 * @return void
	 */
	public function clearContext() {
		$this->roles = NULL;
		$this->tokens = array();
		$this->activeTokens = array();
		$this->inactiveTokens = array();
		$this->request = NULL;
		$this->csrfProtectionTokens = array();
		$this->interceptedRequest = NULL;
		$this->initialized = FALSE;
	}

	/**
	 * Stores all active tokens in $this->activeTokens, all others in $this->inactiveTokens
	 *
	 * @return void
	 */
	protected function separateActiveAndInactiveTokens() {
		if ($this->request === NULL) {
			return;
		}

		foreach ($this->tokens as $token) {
			if ($token->hasRequestPatterns()) {

				$requestPatterns = $token->getRequestPatterns();
				$tokenIsActive = TRUE;

				foreach ($requestPatterns as $requestPattern) {
					$tokenIsActive &= $requestPattern->matchRequest($this->request);
				}
				if ($tokenIsActive) {
					$this->activeTokens[$token->getAuthenticationProviderName()] = $token;
				} else {
					$this->inactiveTokens[$token->getAuthenticationProviderName()] = $token;
				}
			} else {
				$this->activeTokens[$token->getAuthenticationProviderName()] = $token;
			}
		}
	}

	/**
	 * Merges the session and manager tokens. All manager tokens types will be in the result array
	 * If a specific type is found in the session this token replaces the one (of the same type)
	 * given by the manager.
	 *
	 * @param array $managerTokens Array of tokens provided by the authentication manager
	 * @param array $sessionTokens Array of tokens restored from the session
	 * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface objects
	 */
	protected function mergeTokens($managerTokens, $sessionTokens) {
		$resultTokens = array();

		if (!is_array($managerTokens)) {
			return $resultTokens;
		}

		/** @var $managerToken \TYPO3\Flow\Security\Authentication\TokenInterface */
		foreach ($managerTokens as $managerToken) {
			$noCorrespondingSessionTokenFound = TRUE;

			if (!is_array($sessionTokens)) {
				continue;
			}

			/** @var $sessionToken \TYPO3\Flow\Security\Authentication\TokenInterface */
			foreach ($sessionTokens as $sessionToken) {
				if ($sessionToken->getAuthenticationProviderName() === $managerToken->getAuthenticationProviderName()) {
					$resultTokens[$sessionToken->getAuthenticationProviderName()] = $sessionToken;
					$noCorrespondingSessionTokenFound = FALSE;
				}
			}

			if ($noCorrespondingSessionTokenFound) {
				$resultTokens[$managerToken->getAuthenticationProviderName()] = $managerToken;
			}
		}

		return $resultTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array.
	 *
	 * @param array $tokens Array of authentication tokens the credentials should be updated for
	 * @return void
	 */
	protected function updateTokens(array $tokens) {
		if ($this->request !== NULL) {
			foreach ($tokens as $token) {
				$token->updateCredentials($this->request);
			}
		}

		$this->roles = NULL;
	}

	/**
	 * Refreshes all active tokens by updating the credentials.
	 * This is useful when doing an explicit authentication inside a request.
	 *
	 * @return void
	 */
	public function refreshTokens() {
		if ($this->initialized === FALSE) {
			$this->initialize();
		}

		$this->updateTokens($this->activeTokens);
	}

	/**
	 * Shut the object down
	 *
	 * @return void
	 */
	public function shutdownObject() {
		$this->tokens = array_merge($this->inactiveTokens, $this->activeTokens);
		$this->initialized = FALSE;
	}

	/**
	 * Check if the securityContext is ready to be initialized. Only after that security will be active.
	 *
	 * To be able to initialize, there needs to be an ActionRequest available, usually that is
	 * provided by the MVC router.
	 *
	 * @return boolean
	 */
	public function canBeInitialized() {
		if ($this->request === NULL) {
			return FALSE;
		}
		return TRUE;
	}
}

namespace TYPO3\Flow\Security;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * This is the default implementation of a security context, which holds current
 * security information like roles oder details of authenticated users.
 * @\TYPO3\Flow\Annotations\Scope("session")
 */
class Context extends Context_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Security\Context') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Context', $this);
		if ('TYPO3\Flow\Security\Context' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, 'shutdownObject');
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 protected function Flow_Aop_Proxy_buildMethodsAndAdvicesArray() {
		if (method_exists(get_parent_class($this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;
		$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
			'setInterceptedRequest' => array(
				'TYPO3\Flow\Aop\Advice\BeforeAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\BeforeAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'initializeSession', $objectManager, NULL),
				),
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'withoutAuthorizationChecks' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'areAuthorizationChecksDisabled' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'setRequest' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'initialize' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'isInitialized' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getAuthenticationStrategy' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getAuthenticationTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getAuthenticationTokensOfType' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getRoles' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'hasRole' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getParty' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getPartyByType' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getAccount' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getAccountByAuthenticationProviderName' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getCsrfProtectionToken' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'hasCsrfProtectionTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'isCsrfProtectionTokenValid' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'getInterceptedRequest' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'clearContext' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'separateActiveAndInactiveTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'mergeTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'updateTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'refreshTokens' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
			'canBeInitialized' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Session\Aspect\LazyLoadingAspect', 'callMethodOnOriginalSessionObject', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Security\Context') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Context', $this);

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

		\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, 'shutdownObject');
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
	 * @param \TYPO3\Flow\Mvc\ActionRequest $interceptedRequest
	 * @return void
	 * @\TYPO3\Flow\Annotations\Session(autoStart=true)
	 */
	 public function setInterceptedRequest(\TYPO3\Flow\Mvc\ActionRequest $interceptedRequest = NULL) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setInterceptedRequest'])) {
		$result = parent::setInterceptedRequest($interceptedRequest);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['setInterceptedRequest'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['interceptedRequest'] = $interceptedRequest;
			
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['setInterceptedRequest']['TYPO3\Flow\Aop\Advice\BeforeAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'setInterceptedRequest', $methodArguments);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('setInterceptedRequest');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'setInterceptedRequest', $joinPoint->getMethodArguments(), $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setInterceptedRequest']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setInterceptedRequest']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \Closure $callback
	 * @return void
	 * @throws \Exception
	 */
	 public function withoutAuthorizationChecks(\Closure $callback) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['withoutAuthorizationChecks'])) {
		$result = parent::withoutAuthorizationChecks($callback);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['withoutAuthorizationChecks'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['callback'] = $callback;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('withoutAuthorizationChecks');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'withoutAuthorizationChecks', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['withoutAuthorizationChecks']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['withoutAuthorizationChecks']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return boolean
	 */
	 public function areAuthorizationChecksDisabled() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['areAuthorizationChecksDisabled'])) {
		$result = parent::areAuthorizationChecksDisabled();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['areAuthorizationChecksDisabled'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('areAuthorizationChecksDisabled');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'areAuthorizationChecksDisabled', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['areAuthorizationChecksDisabled']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['areAuthorizationChecksDisabled']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The current ActionRequest
	 * @return void
	 * @\TYPO3\Flow\Annotations\Autowiring(enabled=false)
	 */
	 public function setRequest(\TYPO3\Flow\Mvc\ActionRequest $request) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setRequest'])) {
		$result = parent::setRequest($request);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['setRequest'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['request'] = $request;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('setRequest');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'setRequest', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setRequest']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['setRequest']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	 public function initialize() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['initialize'])) {
		$result = parent::initialize();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['initialize'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('initialize');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'initialize', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['initialize']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['initialize']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return boolean TRUE if the Context is initialized, FALSE otherwise.
	 */
	 public function isInitialized() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isInitialized'])) {
		$result = parent::isInitialized();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['isInitialized'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('isInitialized');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'isInitialized', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isInitialized']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isInitialized']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return int One of the AUTHENTICATE_* constants
	 */
	 public function getAuthenticationStrategy() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationStrategy'])) {
		$result = parent::getAuthenticationStrategy();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationStrategy'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getAuthenticationStrategy');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getAuthenticationStrategy', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationStrategy']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationStrategy']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens
	 */
	 public function getAuthenticationTokens() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokens'])) {
		$result = parent::getAuthenticationTokens();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokens'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getAuthenticationTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getAuthenticationTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $className The class name
	 * @return array<\TYPO3\Flow\Security\Authentication\TokenInterface> Array of set tokens of the specified type
	 */
	 public function getAuthenticationTokensOfType($className) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokensOfType'])) {
		$result = parent::getAuthenticationTokensOfType($className);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokensOfType'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['className'] = $className;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getAuthenticationTokensOfType');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getAuthenticationTokensOfType', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokensOfType']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAuthenticationTokensOfType']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return array<\TYPO3\Flow\Security\Policy\Role>
	 */
	 public function getRoles() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getRoles'])) {
		$result = parent::getRoles();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getRoles'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getRoles');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getRoles', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getRoles']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getRoles']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $roleIdentifier The string representation of the role to search for
	 * @return boolean TRUE, if a role with the given string representation was found
	 */
	 public function hasRole($roleIdentifier) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasRole'])) {
		$result = parent::hasRole($roleIdentifier);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['hasRole'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['roleIdentifier'] = $roleIdentifier;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('hasRole');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'hasRole', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasRole']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasRole']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	 public function getParty() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getParty'])) {
		$result = parent::getParty();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getParty'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getParty');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getParty', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getParty']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getParty']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $className Class name of the party to find
	 * @return \TYPO3\Party\Domain\Model\AbstractParty The authenticated party
	 */
	 public function getPartyByType($className) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getPartyByType'])) {
		$result = parent::getPartyByType($className);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getPartyByType'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['className'] = $className;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getPartyByType');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getPartyByType', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getPartyByType']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getPartyByType']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	 public function getAccount() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccount'])) {
		$result = parent::getAccount();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccount'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getAccount');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getAccount', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccount']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccount']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $authenticationProviderName Authentication provider name of the account to find
	 * @return \TYPO3\Flow\Security\Account The authenticated account
	 */
	 public function getAccountByAuthenticationProviderName($authenticationProviderName) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccountByAuthenticationProviderName'])) {
		$result = parent::getAccountByAuthenticationProviderName($authenticationProviderName);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccountByAuthenticationProviderName'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['authenticationProviderName'] = $authenticationProviderName;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getAccountByAuthenticationProviderName');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getAccountByAuthenticationProviderName', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccountByAuthenticationProviderName']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getAccountByAuthenticationProviderName']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return string
	 */
	 public function getCsrfProtectionToken() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getCsrfProtectionToken'])) {
		$result = parent::getCsrfProtectionToken();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getCsrfProtectionToken'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getCsrfProtectionToken');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getCsrfProtectionToken', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getCsrfProtectionToken']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getCsrfProtectionToken']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	 public function hasCsrfProtectionTokens() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasCsrfProtectionTokens'])) {
		$result = parent::hasCsrfProtectionTokens();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['hasCsrfProtectionTokens'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('hasCsrfProtectionTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'hasCsrfProtectionTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasCsrfProtectionTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['hasCsrfProtectionTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param string $csrfToken The token string to be validated
	 * @return boolean TRUE, if the token is valid. FALSE otherwise.
	 */
	 public function isCsrfProtectionTokenValid($csrfToken) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isCsrfProtectionTokenValid'])) {
		$result = parent::isCsrfProtectionTokenValid($csrfToken);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['isCsrfProtectionTokenValid'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['csrfToken'] = $csrfToken;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('isCsrfProtectionTokenValid');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'isCsrfProtectionTokenValid', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isCsrfProtectionTokenValid']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['isCsrfProtectionTokenValid']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	 public function getInterceptedRequest() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getInterceptedRequest'])) {
		$result = parent::getInterceptedRequest();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['getInterceptedRequest'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('getInterceptedRequest');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'getInterceptedRequest', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getInterceptedRequest']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['getInterceptedRequest']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 public function clearContext() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['clearContext'])) {
		$result = parent::clearContext();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['clearContext'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('clearContext');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'clearContext', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['clearContext']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['clearContext']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 protected function separateActiveAndInactiveTokens() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['separateActiveAndInactiveTokens'])) {
		$result = parent::separateActiveAndInactiveTokens();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['separateActiveAndInactiveTokens'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('separateActiveAndInactiveTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'separateActiveAndInactiveTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['separateActiveAndInactiveTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['separateActiveAndInactiveTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param array $managerTokens Array of tokens provided by the authentication manager
	 * @param array $sessionTokens Array of tokens restored from the session
	 * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface objects
	 */
	 protected function mergeTokens($managerTokens, $sessionTokens) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['mergeTokens'])) {
		$result = parent::mergeTokens($managerTokens, $sessionTokens);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['mergeTokens'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['managerTokens'] = $managerTokens;
				$methodArguments['sessionTokens'] = $sessionTokens;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('mergeTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'mergeTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['mergeTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['mergeTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param array $tokens Array of authentication tokens the credentials should be updated for
	 * @return void
	 */
	 protected function updateTokens(array $tokens) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['updateTokens'])) {
		$result = parent::updateTokens($tokens);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['updateTokens'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['tokens'] = $tokens;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('updateTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'updateTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['updateTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['updateTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return void
	 */
	 public function refreshTokens() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['refreshTokens'])) {
		$result = parent::refreshTokens();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['refreshTokens'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('refreshTokens');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'refreshTokens', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['refreshTokens']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['refreshTokens']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @return boolean
	 */
	 public function canBeInitialized() {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['canBeInitialized'])) {
		$result = parent::canBeInitialized();

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['canBeInitialized'] = TRUE;
			try {
			
					$methodArguments = array();

					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('canBeInitialized');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Security\Context', 'canBeInitialized', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['canBeInitialized']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['canBeInitialized']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Context');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Context', $propertyName, 'transient')) continue;
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
		$this->injectAuthenticationManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface'));
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$policyService_reference = &$this->policyService;
		$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Policy\PolicyService');
		if ($this->policyService === NULL) {
			$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('16231078e783810895dba92e364c25f7', $policyService_reference);
			if ($this->policyService === NULL) {
				$this->policyService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('16231078e783810895dba92e364c25f7',  $policyService_reference, 'TYPO3\Flow\Security\Policy\PolicyService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Policy\PolicyService'); });
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
	}
}
#