<?php
namespace TYPO3\Flow\Resource;

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
 * The Resource Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ResourceManager_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $statusCache;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var string
	 */
	protected $persistentResourcesStorageBaseUri;

	/**
	 * @var \SplObjectStorage
	 */
	protected $importedResources;

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
	 * Check for implementations of TYPO3\Flow\Resource\Streams\StreamWrapperInterface and
	 * register them.
	 *
	 * @return void
	 */
	public function initialize() {
		$streamWrapperClassNames = static::getStreamWrapperImplementationClassNames($this->objectManager);
		foreach ($streamWrapperClassNames as $streamWrapperClassName) {
			$scheme = $streamWrapperClassName::getScheme();
			if (in_array($scheme, stream_get_wrappers())) {
				stream_wrapper_unregister($scheme);
			}
			stream_wrapper_register($scheme, '\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter');
			\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::registerStreamWrapper($scheme, $streamWrapperClassName);
		}

			// For now this URI is hardcoded, but might be manageable in the future
			// if additional persistent resources storages are supported.
		$this->persistentResourcesStorageBaseUri = FLOW_PATH_DATA . 'Persistent/Resources/';
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->persistentResourcesStorageBaseUri);

		$this->importedResources = new \SplObjectStorage();
  	}

	/**
	 * Returns all class names implementing the StreamWrapperInterface.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of stream wrapper implementations
	 * @Flow\CompileStatic
	 */
	static public function getStreamWrapperImplementationClassNames($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		return $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
	}

	/**
	 * Imports a resource (file) from the given location as a persistent resource.
	 * On a successful import this method returns a Resource object representing the
	 * newly imported persistent resource.
	 *
	 * @param string $uri An URI (can also be a path and filename) pointing to the resource to import
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 * @api
	 */
	public function importResource($uri) {
		$pathInfo = pathinfo($uri);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			$this->systemLogger->log('Import of resources with a "php" extension is not allowed.', LOG_WARNING);
			return FALSE;
		}

		$temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('Flow_ResourceImport_');
		if (copy($uri, $temporaryTargetPathAndFilename) === FALSE) {
			$this->systemLogger->log('Could not copy resource from "' . $uri . '" to temporary file "' . $temporaryTargetPathAndFilename . '".', LOG_WARNING);
			return FALSE;
		}

		$hash = sha1_file($temporaryTargetPathAndFilename);
		$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
		if (rename($temporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
			unlink($temporaryTargetPathAndFilename);
			$this->systemLogger->log('Could not copy temporary file from "' . $temporaryTargetPathAndFilename. '" to final destination "' . $finalTargetPathAndFilename . '".', LOG_WARNING);
			return FALSE;
		}
		$this->fixFilePermissions($finalTargetPathAndFilename);

		$resource = $this->createResourceFromHashAndFilename($hash, $pathInfo['basename']);
		$this->attachImportedResource($resource);

		return $resource;
	}

	/**
	 * Creates a resource (file) from the given binary content as a persistent resource.
	 * On a successful creation this method returns a Resource object representing the
	 * newly created persistent resource.
	 *
	 * @param mixed $content The binary content of the file
	 * @param string $filename
	 * @return \TYPO3\Flow\Resource\Resource A resource object representing the created resource or FALSE if an error occurred.
	 * @api
	 */
	public function createResourceFromContent($content, $filename) {
		$pathInfo = pathinfo($filename);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			$this->systemLogger->log('Creation of resources with a "php" extension is not allowed.', LOG_WARNING);
			return FALSE;
		}

		$hash = sha1($content);
		$finalTargetPathAndFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->persistentResourcesStorageBaseUri, $hash));
		if (!file_exists($finalTargetPathAndFilename)) {
			if (file_put_contents($finalTargetPathAndFilename, $content) === FALSE) {
				$this->systemLogger->log('Could not create resource at "' . $finalTargetPathAndFilename . '".', LOG_WARNING);
				return FALSE;
			} else {
				$this->fixFilePermissions($finalTargetPathAndFilename);
			}
		}

		$resource = $this->createResourceFromHashAndFilename($hash, $pathInfo['basename']);
		$this->attachImportedResource($resource);

		return $resource;
	}

	/**
	 * Returns an object storage with all resource objects which have been imported
	 * by the Resource Manager during this script call. Each resource comes with
	 * an array of additional information about its import.
	 *
	 * Example for a returned object storage:
	 *
	 * $resource1 => array('originalFilename' => 'Foo.txt'),
	 * $resource2 => array('originalFilename' => 'Bar.txt'),
	 * ...
	 *
	 * @return \SplObjectStorage
	 * @api
	 */
	public function getImportedResources() {
		return clone $this->importedResources;
	}

	/**
	 * Imports a resource (file) from the given upload info array as a persistent
	 * resource.
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 */
	public function importUploadedResource(array $uploadInfo) {
		$pathInfo = pathinfo($uploadInfo['name']);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			return FALSE;
		}

		$temporaryTargetPathAndFilename = $uploadInfo['tmp_name'];
		if (!is_uploaded_file($temporaryTargetPathAndFilename)) {
			return FALSE;
		}

		$openBasedirEnabled = (boolean)ini_get('open_basedir');
		if ($openBasedirEnabled === TRUE) {
				// Move uploaded file to a readable folder before trying to read sha1 value of file
			$newTemporaryTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . uniqid();
			if (move_uploaded_file($temporaryTargetPathAndFilename, $newTemporaryTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
			$hash = sha1_file($newTemporaryTargetPathAndFilename);
			$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
			if (rename($newTemporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
		} else {
			$hash = sha1_file($temporaryTargetPathAndFilename);
			$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
			if (move_uploaded_file($temporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
		}

		$this->fixFilePermissions($finalTargetPathAndFilename);
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($pathInfo['basename']);

		$resourcePointer = $this->getResourcePointerForHash($hash);
		$resource->setResourcePointer($resourcePointer);
		$this->importedResources[$resource] = array(
			'originalFilename' => $pathInfo['basename']
		);
		return $resource;
	}

	/**
	 * Helper function which creates or fetches a resource pointer object for a given hash.
	 *
	 * If a ResourcePointer with the given hash exists, this one is used. Else, a new one
	 * is created. This is a workaround for missing ValueObject support in Doctrine.
	 *
	 * @param string $hash
	 * @return \TYPO3\Flow\Resource\ResourcePointer
	 */
	public function getResourcePointerForHash($hash) {
		$resourcePointer = $this->persistenceManager->getObjectByIdentifier($hash, 'TYPO3\Flow\Resource\ResourcePointer');
		if (!$resourcePointer) {
			$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
			$this->persistenceManager->add($resourcePointer);
		}

		return $resourcePointer;
	}

	/**
	 * Deletes the file represented by the given resource instance.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return boolean
	 */
	public function deleteResource($resource) {
			// instanceof instead of type hinting so it can be used as slot
		if ($resource instanceof \TYPO3\Flow\Resource\Resource) {
			$this->resourcePublisher->unpublishPersistentResource($resource);
			if (is_file($this->persistentResourcesStorageBaseUri . $resource->getResourcePointer()->getHash())) {
				unlink($this->persistentResourcesStorageBaseUri . $resource->getResourcePointer()->getHash());
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Method which returns the base URI of the location where persistent resources
	 * are stored.
	 *
	 * @return string The URI
	 */
	public function getPersistentResourcesStorageBaseUri() {
		return $this->persistentResourcesStorageBaseUri;
	}

	/**
	 * Prepares a mirror of public package resources that is accessible through
	 * the web server directly.
	 *
	 * @param array $activePackages
	 * @return void
	 */
	public function publishPublicPackageResources(array $activePackages) {
		if ($this->settings['resource']['publishing']['detectPackageResourceChanges'] === FALSE && $this->statusCache->has('packageResourcesPublished')) {
			return;
		}
		foreach ($activePackages as $packageKey => $package) {
			$this->resourcePublisher->publishStaticResources($package->getResourcesPath() . 'Public/', 'Packages/' . $packageKey . '/');
		}
		if (!$this->statusCache->has('packageResourcesPublished')) {
			$this->statusCache->set('packageResourcesPublished', 'y', array(\TYPO3\Flow\Cache\Frontend\FrontendInterface::TAG_PACKAGE));
		}
	}

	/**
	 * Fixes the permissions as needed for Flow to run fine in web and cli context.
	 *
	 * @param string $pathAndFilename
	 * @return void
	 */
	protected function fixFilePermissions($pathAndFilename) {
		@chmod($pathAndFilename, 0666 ^ umask());
	}

	/**
	 * Creates a resource object from a given hash and filename. The according
	 * resource pointer is fetched automatically.
	 *
	 * @param string $resourceHash
	 * @param string $originalFilename
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	protected function createResourceFromHashAndFilename($resourceHash, $originalFilename) {
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($originalFilename);

		$resourcePointer = $this->getResourcePointerForHash($resourceHash);
		$resource->setResourcePointer($resourcePointer);

		return $resource;
	}

	/**
	 * Attaches the given resource to the imported resources of this script run
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 */
	protected function attachImportedResource(\TYPO3\Flow\Resource\Resource $resource) {
		$this->importedResources->attach($resource, array(
			'originalFilename' => $resource->getFilename()
		));
	}
}
namespace TYPO3\Flow\Resource;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The Resource Manager
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class ResourceManager extends ResourceManager_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Resource\ResourceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\ResourceManager', $this);
		if ('TYPO3\Flow\Resource\ResourceManager' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Resource\ResourceManager') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Resource\ResourceManager', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Resource\ResourceManager');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Resource\ResourceManager', $propertyName, 'transient')) continue;
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
		$statusCache_reference = &$this->statusCache;
		$this->statusCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('');
		if ($this->statusCache === NULL) {
			$this->statusCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('b3ca84fd627a5045e163e999a38877bf', $statusCache_reference);
			if ($this->statusCache === NULL) {
				$this->statusCache = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('b3ca84fd627a5045e163e999a38877bf',  $statusCache_reference, '', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Resource_Status'); });
			}
		}
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
		$resourcePublisher_reference = &$this->resourcePublisher;
		$this->resourcePublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Resource\Publishing\ResourcePublisher');
		if ($this->resourcePublisher === NULL) {
			$this->resourcePublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('666dcb29134e5c4063bc71f63e10ab36', $resourcePublisher_reference);
			if ($this->resourcePublisher === NULL) {
				$this->resourcePublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('666dcb29134e5c4063bc71f63e10ab36',  $resourcePublisher_reference, 'TYPO3\Flow\Resource\Publishing\ResourcePublisher', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Resource\Publishing\ResourcePublisher'); });
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
		$persistenceManager_reference = &$this->persistenceManager;
		$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		if ($this->persistenceManager === NULL) {
			$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('f1bc82ad47156d95485678e33f27c110', $persistenceManager_reference);
			if ($this->persistenceManager === NULL) {
				$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('f1bc82ad47156d95485678e33f27c110',  $persistenceManager_reference, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'); });
			}
		}
		$systemLogger_reference = &$this->systemLogger;
		$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Log\SystemLoggerInterface');
		if ($this->systemLogger === NULL) {
			$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('6d57d95a1c3cd7528e3e6ea15012dac8', $systemLogger_reference);
			if ($this->systemLogger === NULL) {
				$this->systemLogger = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('6d57d95a1c3cd7528e3e6ea15012dac8',  $systemLogger_reference, 'TYPO3\Flow\Log\SystemLoggerInterface', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'); });
			}
		}
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of stream wrapper implementations
	 * @\TYPO3\Flow\Annotations\CompileStatic
	 */
	static public function getStreamWrapperImplementationClassNames($objectManager) {

return array (
  0 => 'TYPO3\\Flow\\Resource\\Streams\\ResourceStreamWrapper',
);
	}
}
#