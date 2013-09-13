<?php
namespace TYPO3\Fluid\View;

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
use TYPO3\Flow\Http\Request;

/**
 * A standalone template view.
 * Helpful if you want to use Fluid separately from MVC
 * E.g. to generate template based emails.
 *
 * @api
 */
class StandaloneView_Original extends \TYPO3\Fluid\View\AbstractTemplateView {

	/**
	 * Source code of the Fluid template
	 * @var string
	 */
	protected $templateSource = NULL;

	/**
	 * absolute path of the Fluid template
	 * @var string
	 */
	protected $templatePathAndFilename = NULL;

	/**
	 * absolute root path of the folder that contains Fluid layouts
	 * @var string
	 */
	protected $layoutRootPath = NULL;

	/**
	 * absolute root path of the folder that contains Fluid partials
	 * @var string
	 */
	protected $partialRootPath = NULL;

	/**
	 * @var \TYPO3\Fluid\Core\Compiler\TemplateCompiler
	 * @Flow\Inject
	 */
	protected $templateCompiler;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Flow\Mvc\FlashMessageContainer
	 * @Flow\Inject
	 */
	protected $flashMessageContainer;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The current action request. If none is specified it will be created from the environment.
	 */
	public function __construct(\TYPO3\Flow\Mvc\ActionRequest $request = NULL) {
		$this->request = $request;
	}

	/**
	 * Initiates the StandaloneView by creating the required ControllerContext
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->request === NULL) {
			$httpRequest = Request::createFromEnvironment();
			$this->request = $httpRequest->createActionRequest();
		}

		$uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($this->request);

		$this->setControllerContext(new \TYPO3\Flow\Mvc\Controller\ControllerContext(
			$this->request,
			new \TYPO3\Flow\Http\Response(),
			new \TYPO3\Flow\Mvc\Controller\Arguments(array()),
			$uriBuilder,
			$this->flashMessageContainer
		));
	}

	/**
	 * Sets the format of the current request (default format is "html")
	 *
	 * @param string $format
	 * @return void
	 * @api
	 */
	public function setFormat($format) {
		$this->getRequest()->setFormat($format);
	}

	/**
	 * Returns the format of the current request (default is "html")
	 *
	 * @return string $format
	 * @api
	 */
	public function getFormat() {
		return $this->getRequest()->getFormat();
	}

	/**
	 * Returns the current request object
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function getRequest() {
		return $this->controllerContext->getRequest();
	}

	/**
	 * Sets the absolute path to a Fluid template file
	 *
	 * @param string $templatePathAndFilename Fluid template path
	 * @return void
	 * @api
	 */
	public function setTemplatePathAndFilename($templatePathAndFilename) {
		$this->templatePathAndFilename = $templatePathAndFilename;
	}

	/**
	 * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before
	 *
	 * @return string Fluid template path
	 * @api
	 */
	public function getTemplatePathAndFilename() {
		return $this->templatePathAndFilename;
	}

	/**
	 * Sets the Fluid template source
	 * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
	 *
	 * @param string $templateSource Fluid template source code
	 * @return void
	 * @api
	 */
	public function setTemplateSource($templateSource) {
		$this->templateSource = $templateSource;
	}

	/**
	 * Sets the absolute path to the folder that contains Fluid layout files
	 *
	 * @param string $layoutRootPath Fluid layout root path
	 * @return void
	 * @api
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
	}

	/**
	 * Returns the absolute path to the folder that contains Fluid layout files
	 *
	 * @return string Fluid layout root path
	 * @throws Exception\InvalidTemplateResourceException
	 * @api
	 */
	public function getLayoutRootPath() {
		if ($this->layoutRootPath === NULL && $this->templatePathAndFilename === NULL) {
			throw new Exception\InvalidTemplateResourceException('No layout root path has been specified. Use setLayoutRootPath().', 1288091419);
		}
		if ($this->layoutRootPath === NULL) {
			$this->layoutRootPath = dirname($this->templatePathAndFilename) . '/Layouts';
		}
		return $this->layoutRootPath;
	}

	/**
	 * Sets the absolute path to the folder that contains Fluid partial files.
	 *
	 * @param string $partialRootPath Fluid partial root path
	 * @return void
	 * @api
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->partialRootPath = $partialRootPath;
	}

	/**
	 * Returns the absolute path to the folder that contains Fluid partial files
	 *
	 * @return string Fluid partial root path
	 * @throws Exception\InvalidTemplateResourceException
	 * @api
	 */
	public function getPartialRootPath() {
		if ($this->partialRootPath === NULL && $this->templatePathAndFilename === NULL) {
			throw new Exception\InvalidTemplateResourceException('No partial root path has been specified. Use setPartialRootPath().', 1288094511);
		}
		if ($this->partialRootPath === NULL) {
			$this->partialRootPath = dirname($this->templatePathAndFilename) . '/Partials';
		}
		return $this->partialRootPath;
	}

	/**
	 * Checks whether a template can be resolved for the current request
	 *
	 * @return boolean
	 * @api
	 */
	public function hasTemplate() {
		try {
			$this->getTemplateSource();
			return TRUE;
		} catch (Exception\InvalidTemplateResourceException $e) {
			return FALSE;
		}
	}

	/**
	 * Returns a unique identifier for the resolved template file
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $actionName Name of the action. This argument is not used in this view!
	 * @return string template identifier
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getTemplateIdentifier($actionName = NULL) {
		if ($this->templateSource === NULL) {
			$templatePathAndFilename = $this->getTemplatePathAndFilename();
			if ($templatePathAndFilename === NULL) {
				throw new \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException('Neither TemplateSource nor TemplatePathAndFilename have been specified', 1327431077);
			} elseif (!file_exists($templatePathAndFilename)) {
				throw new \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException(sprintf('Template file "%s" could not be loaded', $templatePathAndFilename), 1327431091);
			}
			$templatePathAndFilenameInfo = pathinfo($templatePathAndFilename);
			$templateFilenameWithoutExtension = basename($templatePathAndFilename, '.' . $templatePathAndFilenameInfo['extension']);
			$prefix = sprintf('template_file_%s', $templateFilenameWithoutExtension);
			return $this->createIdentifierForFile($templatePathAndFilename, $prefix);
		} else {
			$templateSource = $this->getTemplateSource();
			$prefix = 'template_source';
			$templateIdentifier = sprintf('Standalone_%s_%s', $prefix, sha1($templateSource));
			return $templateIdentifier;
		}
	}

	/**
	 * Returns the Fluid template source code
	 *
	 * @param string $actionName Name of the action. This argument is not used in this view!
	 * @return string Fluid template source
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getTemplateSource($actionName = NULL) {
		if ($this->templateSource === NULL && $this->templatePathAndFilename === NULL) {
			throw new Exception\InvalidTemplateResourceException('No template has been specified. Use either setTemplateSource() or setTemplatePathAndFilename().', 1288085266);
		}
		if ($this->templateSource === NULL) {
			if (!file_exists($this->templatePathAndFilename)) {
				throw new Exception\InvalidTemplateResourceException('Template could not be found at "' . $this->templatePathAndFilename . '".', 1288087061);
			}
			$this->templateSource = file_get_contents($this->templatePathAndFilename);
		}
		return $this->templateSource;
	}

	/**
	 * Returns a unique identifier for the resolved layout file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $layoutName The name of the layout
	 * @return string layout identifier
	 */
	protected function getLayoutIdentifier($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$prefix = 'layout_' . $layoutName;
		return $this->createIdentifierForFile($layoutPathAndFilename, $prefix);
	}

	/**
	 * Resolves the path and file name of the layout file, based on
	 * $this->getLayoutRootPath() and request format and returns the file contents
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string contents of the layout file if it was found
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === FALSE) {
			throw new Exception\InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1312215888);
		}
		return $layoutSource;
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->getLayoutRootPath() and request format
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout files
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutPathAndFilename($layoutName = 'Default') {
		$layoutRootPath = $this->getLayoutRootPath();
		if (!is_dir($layoutRootPath)) {
			throw new Exception\InvalidTemplateResourceException('Layout root path "' . $layoutRootPath . '" does not exist.', 1288092521);
		}
		$possibleLayoutPaths = array();
		$possibleLayoutPaths[] = \TYPO3\Flow\Utility\Files::getUnixStylePath($layoutRootPath . '/' . $layoutName . '.' . $this->getRequest()->getFormat());
		$possibleLayoutPaths[] = \TYPO3\Flow\Utility\Files::getUnixStylePath($layoutRootPath . '/' . $layoutName);
		foreach ($possibleLayoutPaths as $layoutPathAndFilename) {
			if (file_exists($layoutPathAndFilename)) {
				return $layoutPathAndFilename;
			}
		}
		throw new Exception\InvalidTemplateResourceException('Could not load layout file. Tried following paths: "' . implode('", "', $possibleLayoutPaths) . '".', 1288092555);
	}

	/**
	 * Returns a unique identifier for the resolved partial file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $partialName The name of the partial
	 * @return string partial identifier
	 */
	protected function getPartialIdentifier($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$prefix = 'partial_' . $partialName;
		return $this->createIdentifierForFile($partialPathAndFilename, $prefix);
	}

	/**
	 * Resolves the path and file name of the partial file, based on
	 * $this->getPartialRootPath() and request format and returns the file contents
	 *
	 * @param string $partialName The name of the partial
	 * @return string contents of the layout file if it was found
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === FALSE) {
			throw new Exception\InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $partialSource;
	}

	/**
	 * Resolve the partial path and filename based on $this->getPartialRootPath() and request format
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getPartialPathAndFilename($partialName) {
		$partialRootPath = $this->getPartialRootPath();
		if (!is_dir($partialRootPath)) {
			throw new Exception\InvalidTemplateResourceException('Partial root path "' . $partialRootPath . '" does not exist.', 1288094648);
		}
		$possiblePartialPaths = array();
		$possiblePartialPaths[] = \TYPO3\Flow\Utility\Files::getUnixStylePath($partialRootPath . '/' . $partialName . '.' . $this->getRequest()->getFormat());
		$possiblePartialPaths[] = \TYPO3\Flow\Utility\Files::getUnixStylePath($partialRootPath . '/' . $partialName);
		foreach ($possiblePartialPaths as $partialPathAndFilename) {
			if (file_exists($partialPathAndFilename)) {
				return $partialPathAndFilename;
			}
		}
		throw new Exception\InvalidTemplateResourceException('Could not load partial file. Tried following paths: "' . implode('", "', $possiblePartialPaths) . '".', 1288092555);
	}

	/**
	 * Returns a unique identifier for the given file in the format
	 * Standalone_<prefix>_<SHA1>
	 * The SHA1 hash is a checksum that is based on the file path and last modification date
	 *
	 * @param string $pathAndFilename
	 * @param string $prefix
	 * @return string
	 */
	protected function createIdentifierForFile($pathAndFilename, $prefix) {
		$templateModifiedTimestamp = filemtime($pathAndFilename);
		$templateIdentifier = sprintf('Standalone_%s_%s', $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
		$templateIdentifier = str_replace('/', '_', str_replace('.', '_', $templateIdentifier));
		return $templateIdentifier;
	}
}
namespace TYPO3\Fluid\View;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A standalone template view.
 * Helpful if you want to use Fluid separately from MVC
 * E.g. to generate template based emails.
 */
class StandaloneView extends StandaloneView_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The current action request. If none is specified it will be created from the environment.
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Fluid\View\StandaloneView' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
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
		$result = NULL;

		$this->initializeObject(2);
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\View\StandaloneView');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\View\StandaloneView', $propertyName, 'transient')) continue;
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
		$this->setRenderingContext(new \TYPO3\Fluid\Core\Rendering\RenderingContext());
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectTemplateParser(new \TYPO3\Fluid\Core\Parser\TemplateParser());
		$this->injectTemplateCompiler(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Fluid\Core\Compiler\TemplateCompiler'));
		$environment_reference = &$this->environment;
		$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Utility\Environment');
		if ($this->environment === NULL) {
			$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('d7473831479e64d04a54de9aedcdc371', $environment_reference);
			if ($this->environment === NULL) {
				$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('d7473831479e64d04a54de9aedcdc371',  $environment_reference, 'TYPO3\Flow\Utility\Environment', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment'); });
			}
		}
		$flashMessageContainer_reference = &$this->flashMessageContainer;
		$this->flashMessageContainer = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Mvc\FlashMessageContainer');
		if ($this->flashMessageContainer === NULL) {
			$this->flashMessageContainer = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('e4fd26f8afd3994317304b563b2a9561', $flashMessageContainer_reference);
			if ($this->flashMessageContainer === NULL) {
				$this->flashMessageContainer = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('e4fd26f8afd3994317304b563b2a9561',  $flashMessageContainer_reference, 'TYPO3\Flow\Mvc\FlashMessageContainer', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Mvc\FlashMessageContainer'); });
			}
		}
	}
}
#