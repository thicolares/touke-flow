<?php
namespace TYPO3\Flow\Resource\Publishing;

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

/**
 * Publishing target for a file system.
 *
 * @Flow\Scope("singleton")
 */
class FileSystemPublishingTarget_Original extends \TYPO3\Flow\Resource\Publishing\AbstractResourcePublishingTarget {

	/**
	 * @var string
	 */
	protected $resourcesPublishingPath;

	/**
	 * @var \TYPO3\Flow\Http\Uri
	 */
	protected $resourcesBaseUri;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Injects the bootstrap
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function injectBootstrap(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes this publishing target
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Resource\Exception
	 */
	public function initializeObject() {
		if ($this->resourcesPublishingPath === NULL) {
			$this->resourcesPublishingPath = FLOW_PATH_WEB . '_Resources/';
		}

		if (!is_writable($this->resourcesPublishingPath)) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath);
		}
		if (!is_dir($this->resourcesPublishingPath) && !is_link($this->resourcesPublishingPath)) {
			throw new \TYPO3\Flow\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" does not exist.', 1207124538);
		}
		if (!is_writable($this->resourcesPublishingPath)) {
			throw new \TYPO3\Flow\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" is not writable.', 1207124546);
		}
		if (!is_dir($this->resourcesPublishingPath . 'Persistent') && !is_link($this->resourcesPublishingPath . 'Persistent')) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath . 'Persistent');
		}
		if (!is_writable($this->resourcesPublishingPath . 'Persistent')) {
			throw new \TYPO3\Flow\Resource\Exception('The directory "' . $this->resourcesPublishingPath . 'Persistent" is not writable.', 1260527881);
		}
	}

	/**
	 * Recursively publishes static resources located in the specified directory.
	 * These resources are typically public package resources provided by the active packages.
	 *
	 * @param string $sourcePath The full path to the source directory which should be published (includes sub directories)
	 * @param string $relativeTargetPath Path relative to the target's root where resources should be published to.
	 * @return boolean TRUE if publication succeeded or FALSE if the resources could not be published
	 */
	public function publishStaticResources($sourcePath, $relativeTargetPath) {
		if (!is_dir($sourcePath)) {
			return FALSE;
		}
		$sourcePath = rtrim(\TYPO3\Flow\Utility\Files::getUnixStylePath($this->realpath($sourcePath)), '/');
		$targetPath = rtrim(\TYPO3\Flow\Utility\Files::concatenatePaths(array($this->resourcesPublishingPath, 'Static', $relativeTargetPath)), '/');

		if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {
			if (\TYPO3\Flow\Utility\Files::is_link($targetPath) && (rtrim(\TYPO3\Flow\Utility\Files::getUnixStylePath($this->realpath($targetPath)), '/') === $sourcePath)) {
				return TRUE;
			} elseif (is_dir($targetPath)) {
				\TYPO3\Flow\Utility\Files::removeDirectoryRecursively($targetPath);
			} elseif (is_link($targetPath)) {
				unlink($targetPath);
			} else {
				\TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($targetPath));
			}
			symlink($sourcePath, $targetPath);
		} else {
			foreach (\TYPO3\Flow\Utility\Files::readDirectoryRecursively($sourcePath) as $sourcePathAndFilename) {
				if (substr(strtolower($sourcePathAndFilename), -4, 4) === '.php') continue;
				$targetPathAndFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($targetPath, str_replace($sourcePath, '', $sourcePathAndFilename)));
				if (!file_exists($targetPathAndFilename) || filemtime($sourcePathAndFilename) > filemtime($targetPathAndFilename)) {
					$this->mirrorFile($sourcePathAndFilename, $targetPathAndFilename, TRUE);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Publishes a persistent resource to the web accessible resources directory.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function publishPersistentResource(\TYPO3\Flow\Resource\Resource $resource) {
		$publishedResourcePathAndFilename = $this->buildPersistentResourcePublishPathAndFilename($resource, TRUE);
		$publishedResourceWebUri = $this->buildPersistentResourceWebUri($resource);

		if (!file_exists($publishedResourcePathAndFilename)) {
			$unpublishedResourcePathAndFilename = $this->getPersistentResourceSourcePathAndFilename($resource);
			if ($unpublishedResourcePathAndFilename === FALSE) {
				return FALSE;
			}
			$this->mirrorFile($unpublishedResourcePathAndFilename, $publishedResourcePathAndFilename, FALSE);
		}
		return $publishedResourceWebUri;
	}

	/**
	 * Unpublishes a persistent resource in the web accessible resources directory.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to unpublish
	 * @return boolean TRUE if at least one file was removed, FALSE otherwise
	 */
	public function unpublishPersistentResource(\TYPO3\Flow\Resource\Resource $resource) {
		$result = FALSE;
		foreach (glob($this->buildPersistentResourcePublishPathAndFilename($resource, FALSE) . '*') as $publishedResourcePathAndFilename) {
			unlink($publishedResourcePathAndFilename);
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Returns the base URI where persistent resources are published an accessible from the outside.
	 *
	 * @return \TYPO3\Flow\Http\Uri The base URI
	 */
	public function getResourcesBaseUri() {
		if ($this->resourcesBaseUri === NULL) {
			$this->detectResourcesBaseUri();
		}
		return $this->resourcesBaseUri;
	}

	/**
	 * Returns the publishing path where resources are published in the local filesystem
	 * @return string The resources publishing path
	 */
	public function getResourcesPublishingPath() {
		return $this->resourcesPublishingPath;
	}

	/**
	 * Returns the base URI pointing to the published static resources
	 *
	 * @return string The base URI pointing to web accessible static resources
	 */
	public function getStaticResourcesWebBaseUri() {
		return $this->getResourcesBaseUri() . 'Static/';
	}

	/**
	 * Returns the web URI pointing to the published persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function getPersistentResourceWebUri(\TYPO3\Flow\Resource\Resource $resource) {
		return $this->publishPersistentResource($resource);
	}

	/**
	 * Detects the (resources) base URI and stores it as a protected class variable.
	 *
	 * $this->resourcesPublishingPath must be set prior to calling this method.
	 *
	 * @return void
	 */
	protected function detectResourcesBaseUri() {
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
			$uri = $requestHandler->getHttpRequest()->getBaseUri();
		} else {
			$uri = '';
		}
		$this->resourcesBaseUri = $uri . substr($this->resourcesPublishingPath, strlen(FLOW_PATH_WEB));
	}

	/**
	 * Depending on the settings of this publishing target copies the specified file
	 * or creates a symbolic link.
	 *
	 * @param string $sourcePathAndFilename
	 * @param string $targetPathAndFilename
	 * @param boolean $createDirectoriesIfNecessary
	 * @return void
	 * @throws \TYPO3\Flow\Resource\Exception
	 */
	protected function mirrorFile($sourcePathAndFilename, $targetPathAndFilename, $createDirectoriesIfNecessary = FALSE) {
		if ($createDirectoriesIfNecessary === TRUE) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		switch ($this->settings['resource']['publishing']['fileSystem']['mirrorMode']) {
			case 'copy' :
				copy($sourcePathAndFilename, $targetPathAndFilename);
				touch($targetPathAndFilename, filemtime($sourcePathAndFilename));
				break;
			case 'link' :
				if (file_exists($targetPathAndFilename)) {
					if (\TYPO3\Flow\Utility\Files::is_link($targetPathAndFilename) && ($this->realpath($targetPathAndFilename) === $this->realpath($sourcePathAndFilename))) {
						break;
					}
					unlink($targetPathAndFilename);
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				} else {
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				}
				break;
			default :
				throw new \TYPO3\Flow\Resource\Exception('An invalid mirror mode (' . $this->settings['resource']['publishing']['fileSystem']['mirrorMode'] . ') has been configured.', 1256133400);
		}

		if (!file_exists($targetPathAndFilename)) {
			throw new \TYPO3\Flow\Resource\Exception('The resource "' . $sourcePathAndFilename . '" could not be mirrored.', 1207255453);
		}
	}

	/**
	 * Returns the web URI to be used to publish the specified persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to build the URI for
	 * @return string The web URI
	 */
	protected function buildPersistentResourceWebUri(\TYPO3\Flow\Resource\Resource $resource) {
		$filename = $resource->getFilename();
		$rewrittenFilename = ($filename === '' || $filename === NULL) ? '' : '/' . $this->rewriteFilenameForUri($filename);
		return $this->getResourcesBaseUri() . 'Persistent/' . $resource->getResourcePointer()->getHash() . $rewrittenFilename;
	}

	/**
	 * Returns the publish path and filename to be used to publish the specified persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to build the publish path and filename for
	 * @param boolean $returnFilename FALSE if only the directory without the filename should be returned
	 * @return string The publish path and filename
	 */
	protected function buildPersistentResourcePublishPathAndFilename(\TYPO3\Flow\Resource\Resource $resource, $returnFilename) {
		$publishPath = $this->resourcesPublishingPath . 'Persistent/';
		if ($returnFilename === TRUE) return $publishPath . $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();
		return $publishPath;
	}

	/**
	 * Wrapper around realpath(). Needed for testing, as realpath() cannot be mocked
	 * by vfsStream.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function realpath($path) {
		return realpath($path);
	}
}

namespace TYPO3\Flow\Resource\Publishing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Publishing target for a file system.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class FileSystemPublishingTarget extends FileSystemPublishingTarget_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	private $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array();

	private $Flow_Aop_Proxy_groupedAdviceChains = array();

	private $Flow_Aop_Proxy_methodIsInAdviceMode = array();


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', $this);
		if (get_class($this) === 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\Publishing\ResourcePublishingTargetInterface', $this);
		if ('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 protected function Flow_Aop_Proxy_buildMethodsAndAdvicesArray() {
		if (method_exists(get_parent_class($this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();

		$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;
		$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
			'buildPersistentResourceWebUri' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', 'rewritePersistentResourceWebUriForPrivateResources', $objectManager, NULL),
				),
			),
			'buildPersistentResourcePublishPathAndFilename' => array(
				'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', 'rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $objectManager, NULL),
				),
			),
			'unpublishPersistentResource' => array(
				'TYPO3\Flow\Aop\Advice\AfterAdvice' => array(
					new \TYPO3\Flow\Aop\Advice\AfterAdvice('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', 'unpublishPrivateResource', $objectManager, NULL),
				),
			),
		);
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

		$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
		if (get_class($this) === 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', $this);
		if (get_class($this) === 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\Publishing\ResourcePublishingTargetInterface', $this);

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

		$this->initializeObject(2);
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
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to build the URI for
	 * @return string The web URI
	 */
	 protected function buildPersistentResourceWebUri(\TYPO3\Flow\Resource\Resource $resource) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourceWebUri'])) {
		$result = parent::buildPersistentResourceWebUri($resource);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourceWebUri'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['resource'] = $resource;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('buildPersistentResourceWebUri');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', 'buildPersistentResourceWebUri', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourceWebUri']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourceWebUri']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to build the publish path and filename for
	 * @param boolean $returnFilename FALSE if only the directory without the filename should be returned
	 * @return string The publish path and filename
	 */
	 protected function buildPersistentResourcePublishPathAndFilename(\TYPO3\Flow\Resource\Resource $resource, $returnFilename) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourcePublishPathAndFilename'])) {
		$result = parent::buildPersistentResourcePublishPathAndFilename($resource, $returnFilename);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourcePublishPathAndFilename'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['resource'] = $resource;
				$methodArguments['returnFilename'] = $returnFilename;
			
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains('buildPersistentResourcePublishPathAndFilename');
					$adviceChain = $adviceChains['TYPO3\Flow\Aop\Advice\AroundAdvice'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', 'buildPersistentResourcePublishPathAndFilename', $methodArguments, $adviceChain);
					$result = $adviceChain->proceed($joinPoint);

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourcePublishPathAndFilename']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['buildPersistentResourcePublishPathAndFilename']);
		}
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to unpublish
	 * @return boolean TRUE if at least one file was removed, FALSE otherwise
	 */
	 public function unpublishPersistentResource(\TYPO3\Flow\Resource\Resource $resource) {

				// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
			$this->Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode['unpublishPersistentResource'])) {
		$result = parent::unpublishPersistentResource($resource);

		} else {
			$this->Flow_Aop_Proxy_methodIsInAdviceMode['unpublishPersistentResource'] = TRUE;
			try {
			
					$methodArguments = array();

				$methodArguments['resource'] = $resource;
			
		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', 'unpublishPersistentResource', $methodArguments);
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);

					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['unpublishPersistentResource']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', 'unpublishPersistentResource', $joinPoint->getMethodArguments(), NULL, $result);
					$afterAdviceInvoked = TRUE;
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}

			} catch (\Exception $exception) {

				if (!$afterAdviceInvoked) {
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices['unpublishPersistentResource']['TYPO3\Flow\Aop\Advice\AfterAdvice'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, 'TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', 'unpublishPersistentResource', $joinPoint->getMethodArguments(), NULL, NULL, $exception);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
				}

				throw $exception;
		}

			} catch (\Exception $e) {
				unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['unpublishPersistentResource']);
				throw $e;
			}
			unset($this->Flow_Aop_Proxy_methodIsInAdviceMode['unpublishPersistentResource']);
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', $propertyName, 'transient')) continue;
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
		$this->injectBootstrap(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap'));
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
	}
}
#