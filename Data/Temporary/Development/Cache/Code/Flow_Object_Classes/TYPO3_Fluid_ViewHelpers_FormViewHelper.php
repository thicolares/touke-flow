<?php
namespace TYPO3\Fluid\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Form view helper. Generates a <form> Tag.
 *
 * = Basic usage =
 *
 * Use <f:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <f:form action="...">...</f:form>
 * </code>
 *
 * = A complex form with a specified encoding type =
 *
 * <code title="Form with enctype set">
 * <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 *
 * = A Form which should render a domain object =
 *
 * <code title="Binding a domain object to a form">
 * <f:form action="..." name="customer" object="{customer}">
 *   <f:form.hidden property="id" />
 *   <f:form.textfield property="name" />
 * </f:form>
 * </code>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 *
 * @api
 */
class FormViewHelper_Original extends \TYPO3\Fluid\ViewHelpers\Form\AbstractFormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService
	 */
	protected $mvcPropertyMappingConfigurationService;

	/**
	 * @var string
	 */
	protected $formActionUri;

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
		$this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
		$this->registerTagAttribute('name', 'string', 'Name of form');
		$this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
		$this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');

		$this->registerUniversalTagAttributes();
	}

	/**
	 * Render the form.
	 *
	 * @param string $action target action
	 * @param array $arguments additional arguments
	 * @param string $controller name of target controller
	 * @param string $package name of target package
	 * @param string $subpackage name of target subpackage
	 * @param mixed $object object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @param string $section The anchor to be added to the action URI (only active if $actionUri is not set)
	 * @param string $format The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
	 * @param array $additionalParams additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
	 * @param boolean $absolute If set, an absolute action URI is rendered (only active if $actionUri is not set)
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
	 * @param string $fieldNamePrefix Prefix that will be added to all field names within this form
	 * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
	 * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
	 * @param boolean $useParentRequest If set, the parent Request will be used instead ob the current one
	 * @return string rendered form
	 * @api
	 * @throws \TYPO3\Fluid\Core\ViewHelper\Exception
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $package = NULL, $subpackage = NULL, $object = NULL, $section = '', $format = '', array $additionalParams = array(), $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $fieldNamePrefix = NULL, $actionUri = NULL, $objectName = NULL, $useParentRequest = FALSE) {
		$this->formActionUri = NULL;
		if ($action === NULL && $actionUri === NULL) {
			throw new \TYPO3\Fluid\Core\ViewHelper\Exception('FormViewHelper requires "actionUri" or "action" argument to be specified', 1355243748);
		}
		$this->tag->addAttribute('action', $this->getFormActionUri());

		if (strtolower($this->arguments['method']) === 'get') {
			$this->tag->addAttribute('method', 'get');
		} else {
			$this->tag->addAttribute('method', 'post');
		}

		$this->addFormObjectNameToViewHelperVariableContainer();
		$this->addFormObjectToViewHelperVariableContainer();
		$this->addFieldNamePrefixToViewHelperVariableContainer();
		$this->addFormFieldNamesToViewHelperVariableContainer();
		$this->addEmptyHiddenFieldNamesToViewHelperVariableContainer();

		$formContent = $this->renderChildren();

			// wrap hidden field in div container in order to create XHTML valid output
		$content = chr(10) . '<div style="display: none">';
		if (strtolower($this->arguments['method']) === 'get') {
			$content .= $this->renderHiddenActionUriQueryParameters();
		}
		$content .= $this->renderHiddenIdentityField($this->arguments['object'], $this->getFormObjectName());
		$content .= $this->renderAdditionalIdentityFields();
		$content .= $this->renderHiddenReferrerFields();
		$content .= $this->renderEmptyHiddenFields();
			// Render the trusted list of all properties after everything else has been rendered
		$content .= $this->renderTrustedPropertiesField();
		if (strtolower($this->arguments['method']) !== 'get') {
			$content .= $this->renderCsrfTokenField();
		}
		$content .= chr(10) . '</div>' . chr(10);
		$content .= $formContent;

		$this->tag->setContent($content);

		$this->removeFieldNamePrefixFromViewHelperVariableContainer();
		$this->removeFormObjectFromViewHelperVariableContainer();
		$this->removeFormObjectNameFromViewHelperVariableContainer();
		$this->removeFormFieldNamesFromViewHelperVariableContainer();
		$this->removeEmptyHiddenFieldNamesFromViewHelperVariableContainer();

		return $this->tag->render();
	}

	/**
	 * Returns the action URI of the form tag.
	 * If the argument "actionUri" is specified, this will be returned
	 * Otherwise this creates the action URI using the UriBuilder
	 *
	 * @return string
	 * @throws \TYPO3\Fluid\Core\ViewHelper\Exception if the action URI could not be created
	 */
	protected function getFormActionUri() {
		if ($this->formActionUri !== NULL) {
			return $this->formActionUri;
		}
		if ($this->hasArgument('actionUri')) {
			$this->formActionUri = $this->arguments['actionUri'];
		} else {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			if ($this->arguments['useParentRequest'] === TRUE) {
				$request = $this->controllerContext->getRequest();
				if ($request->isMainRequest()) {
					throw new \TYPO3\Fluid\Core\ViewHelper\Exception('You can\'t use the parent Request, you are already in the MainRequest.', 1361354942);
				}
				$uriBuilder = clone $uriBuilder;
				$uriBuilder->setRequest($request->getParentRequest());
			}
			$uriBuilder
				->reset()
				->setSection($this->arguments['section'])
				->setCreateAbsoluteUri($this->arguments['absolute'])
				->setAddQueryString($this->arguments['addQueryString'])
				->setFormat($this->arguments['format']);
			if (is_array($this->arguments['additionalParams'])) {
				$uriBuilder->setArguments($this->arguments['additionalParams']);
			}
			if (is_array($this->arguments['argumentsToBeExcludedFromQueryString'])) {
				$uriBuilder->setArgumentsToBeExcludedFromQueryString($this->arguments['argumentsToBeExcludedFromQueryString']);
			}
			try {
				$this->formActionUri = $uriBuilder
					->uriFor($this->arguments['action'], $this->arguments['arguments'], $this->arguments['controller'], $this->arguments['package'], $this->arguments['subpackage']);
			} catch (\TYPO3\Flow\Exception $exception) {
				throw new \TYPO3\Fluid\Core\ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
			}
		}
		return $this->formActionUri;
	}

	/**
	 * Render hidden form fields for query parameters from action URI.
	 * This is only needed if the form method is GET.
	 *
	 * @return string Hidden fields for query parameters from action URI
	 */
	protected function renderHiddenActionUriQueryParameters() {
		$result = '';
		$actionUri = $this->getFormActionUri();
		$query = parse_url($actionUri, PHP_URL_QUERY);

		if (is_string($query)) {
			$queryParts = explode('&', $query);
			foreach ($queryParts as $queryPart) {
				if (strpos($queryPart, '=') !== FALSE) {
					list($parameterName, $parameterValue) = explode('=', $queryPart, 2);
					$result .= chr(10) . '<input type="hidden" name="' . htmlentities(urldecode($parameterName)) . '" value="' . htmlentities(urldecode($parameterValue)) . '" />';
				}
			}
		}
		return $result;
	}

	/**
	 * Render additional identity fields which were registered by form elements.
	 * This happens if a form field is defined like property="bla.blubb" - then we might need an identity property for the sub-object "bla".
	 *
	 * @return string HTML-string for the additional identity properties
	 */
	protected function renderAdditionalIdentityFields() {
		if ($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'additionalIdentityProperties')) {
			$additionalIdentityProperties = $this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'additionalIdentityProperties');
			$output = '';
			foreach ($additionalIdentityProperties as $identity) {
				$output .= chr(10) . $identity;
			}
			return $output;
		}
		return '';
	}

	/**
	 * Renders hidden form fields for referrer information about
	 * the current controller and action.
	 *
	 * @return string Hidden fields with referrer information
	 * @todo filter out referrer information that is equal to the target (e.g. same packageKey)
	 */
	protected function renderHiddenReferrerFields() {
		$result = chr(10);
		$request = $this->controllerContext->getRequest();
		$argumentNamespace = NULL;
		if (!$request->isMainRequest()) {
			$argumentNamespace = $request->getArgumentNamespace();

			$referrer = array(
				'@package' => $request->getControllerPackageKey(),
				'@subpackage' => $request->getControllerSubpackageKey(),
				'@controller' => $request->getControllerName(),
				'@action' => $request->getControllerActionName(),
				'arguments' => $this->hashService->appendHmac(base64_encode(serialize($request->getArguments())))
			);
			foreach ($referrer as $referrerKey => $referrerValue) {
				$referrerValue = \htmlspecialchars($referrerValue);
				$result .= '<input type="hidden" name="' . $argumentNamespace . '[__referrer][' . $referrerKey . ']" value="' . $referrerValue . '" />' . chr(10);
			}
			$request = $request->getParentRequest();
		}

		$arguments = $request->getArguments();
		if ($argumentNamespace !== NULL && isset($arguments[$argumentNamespace])) {
				// A sub request was there; thus we can unset the sub requests arguments,
				// as they are transferred separately via the code block shown above.
			unset($arguments[$argumentNamespace]);
		}

		$referrer = array(
			'@package' => $request->getControllerPackageKey(),
			'@subpackage' => $request->getControllerSubpackageKey(),
			'@controller' => $request->getControllerName(),
			'@action' => $request->getControllerActionName(),
			'arguments' => $this->hashService->appendHmac(base64_encode(serialize($arguments)))
		);

		foreach ($referrer as $referrerKey => $referrerValue) {
			$result .= '<input type="hidden" name="__referrer[' . $referrerKey . ']' . '" value="' . htmlspecialchars($referrerValue) . '" />' . chr(10);
		}
		return $result;
	}

	/**
	 * Adds the form object name to the ViewHelperVariableContainer if "objectName" argument or "name" attribute is specified.
	 *
	 * @return void
	 */
	protected function addFormObjectNameToViewHelperVariableContainer() {
		$formObjectName = $this->getFormObjectName();
		if ($formObjectName !== NULL) {
			$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formObjectName', $formObjectName);
		}
	}

	/**
	 * Removes the form object name from the ViewHelperVariableContainer.
	 *
	 * @return void
	 */
	protected function removeFormObjectNameFromViewHelperVariableContainer() {
		$formObjectName = $this->getFormObjectName();
		if ($formObjectName !== NULL) {
			$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formObjectName');
		}
	}

	/**
	 * Returns the name of the object that is bound to this form.
	 * If the "objectName" argument has been specified, this is returned. Otherwise the name attribute of this form.
	 * If neither objectName nor name arguments have been set, NULL is returned.
	 *
	 * @return string specified Form name or NULL if neither $objectName nor $name arguments have been specified
	 */
	protected function getFormObjectName() {
		$formObjectName = NULL;
		if ($this->hasArgument('objectName')) {
			$formObjectName = $this->arguments['objectName'];
		} elseif ($this->hasArgument('name')) {
			$formObjectName = $this->arguments['name'];
		}
		return $formObjectName;
	}

	/**
	 * Adds the object that is bound to this form to the ViewHelperVariableContainer if the formObject attribute is specified.
	 *
	 * @return void
	 */
	protected function addFormObjectToViewHelperVariableContainer() {
		if ($this->hasArgument('object')) {
			$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formObject', $this->arguments['object']);
			$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'additionalIdentityProperties', array());
		}
	}

	/**
	 * Removes the form object from the ViewHelperVariableContainer.
	 *
	 * @return void
	 */
	protected function removeFormObjectFromViewHelperVariableContainer() {
		if ($this->hasArgument('object')) {
			$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formObject');
			$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'additionalIdentityProperties');
		}
	}

	/**
	 * Adds the field name prefix to the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function addFieldNamePrefixToViewHelperVariableContainer() {
		$fieldNamePrefix = $this->getFieldNamePrefix();
		$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'fieldNamePrefix', $fieldNamePrefix);
	}

	/**
	 * Get the field name prefix
	 *
	 * @return string
	 */
	protected function getFieldNamePrefix() {
		if ($this->hasArgument('fieldNamePrefix')) {
			return $this->arguments['fieldNamePrefix'];
		} else {
			return $this->getDefaultFieldNamePrefix();
		}
	}

	/**
	 * Retrieves the default field name prefix for this form
	 *
	 * @return string default field name prefix
	 */
	protected function getDefaultFieldNamePrefix() {
		$request = $this->controllerContext->getRequest();
		if (!$request->isMainRequest()) {
			if ($this->arguments['useParentRequest'] === TRUE) {
				return $request->getParentRequest()->getArgumentNamespace();
			} else {
				return $request->getArgumentNamespace();
			}
		}
		return '';
	}

	/**
	 * Removes field name prefix from the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function removeFieldNamePrefixFromViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'fieldNamePrefix');
	}

	/**
	 * Adds a container for form field names to the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function addFormFieldNamesToViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formFieldNames', array());
	}

	/**
	 * Removes the container for form field names from the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function removeFormFieldNamesFromViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formFieldNames');
	}

	/**
	 * Adds a container for rendered hidden field names for empty values to the ViewHelperVariableContainer
	 * @see \TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::renderHiddenFieldForEmptyValue()
	 *
	 * @return void
	 */
	protected function addEmptyHiddenFieldNamesToViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames', array());
	}

	/**
	 * Removes container for rendered hidden field names for empty values from ViewHelperVariableContainer
	 * @see \TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::renderHiddenFieldForEmptyValue()
	 *
	 * @return void
	 */
	protected function removeEmptyHiddenFieldNamesFromViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames');
	}

	/**
	 * Renders all empty hidden fields that have been added to ViewHelperVariableContainer
	 * @see \TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::renderHiddenFieldForEmptyValue()
	 *
	 * @return string
	 */
	protected function renderEmptyHiddenFields() {
		$result = '';
		if ($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames')) {
			$emptyHiddenFieldNames = $this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames');
			foreach ($emptyHiddenFieldNames as $hiddenFieldName) {
				$result .= '<input type="hidden" name="' . htmlspecialchars($hiddenFieldName) . '" value="" />' . chr(10);
			}
		}
		return $result;
	}

	/**
	 * Render the request hash field
	 *
	 * @return string the hmac field
	 */
	protected function renderTrustedPropertiesField() {
		$formFieldNames = $this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'formFieldNames');
		$requestHash = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $this->getFieldNamePrefix());
		return '<input type="hidden" name="' . $this->prefixFieldName('__trustedProperties') . '" value="' . htmlspecialchars($requestHash) . '" />' . chr(10);
	}

	/**
	 * Render the a hidden field with a CSRF token
	 *
	 * @return string the CSRF token field
	 */
	protected function renderCsrfTokenField() {
		if (!$this->securityContext->isInitialized()) {
			return '';
		}
		$csrfToken = $this->securityContext->getCsrfProtectionToken();
		return '<input type="hidden" name="__csrfToken" value="' . htmlspecialchars($csrfToken) . '" />' . chr(10);
	}
}
namespace TYPO3\Fluid\ViewHelpers;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Form view helper. Generates a <form> Tag.
 * 
 * = Basic usage =
 * 
 * Use <f:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <f:form action="...">...</f:form>
 * </code>
 * 
 * = A complex form with a specified encoding type =
 * 
 * <code title="Form with enctype set">
 * <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 * 
 * = A Form which should render a domain object =
 * 
 * <code title="Binding a domain object to a form">
 * <f:form action="..." name="customer" object="{customer}">
 *   <f:form.hidden property="id" />
 *   <f:form.textfield property="name" />
 * </f:form>
 * </code>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 */
class FormViewHelper extends FormViewHelper_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Fluid\ViewHelpers\FormViewHelper' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\ViewHelpers\FormViewHelper');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\ViewHelpers\FormViewHelper', $propertyName, 'transient')) continue;
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
		$this->injectPersistenceManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'));
		$this->injectTagBuilder(new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('', ''));
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectSystemLogger(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$hashService_reference = &$this->hashService;
		$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Cryptography\HashService');
		if ($this->hashService === NULL) {
			$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('af606f3838da2ad86bf0ed2ff61be394', $hashService_reference);
			if ($this->hashService === NULL) {
				$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('af606f3838da2ad86bf0ed2ff61be394',  $hashService_reference, 'TYPO3\Flow\Security\Cryptography\HashService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Cryptography\HashService'); });
			}
		}
		$securityContext_reference = &$this->securityContext;
		$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Context');
		if ($this->securityContext === NULL) {
			$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('48836470c14129ade5f39e28c4816673', $securityContext_reference);
			if ($this->securityContext === NULL) {
				$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('48836470c14129ade5f39e28c4816673',  $securityContext_reference, 'TYPO3\Flow\Security\Context', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Context'); });
			}
		}
		$mvcPropertyMappingConfigurationService_reference = &$this->mvcPropertyMappingConfigurationService;
		$this->mvcPropertyMappingConfigurationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService');
		if ($this->mvcPropertyMappingConfigurationService === NULL) {
			$this->mvcPropertyMappingConfigurationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('35acb49fbe78f28099d45aa647797c83', $mvcPropertyMappingConfigurationService_reference);
			if ($this->mvcPropertyMappingConfigurationService === NULL) {
				$this->mvcPropertyMappingConfigurationService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('35acb49fbe78f28099d45aa647797c83',  $mvcPropertyMappingConfigurationService_reference, 'TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService'); });
			}
		}
	}
}
#