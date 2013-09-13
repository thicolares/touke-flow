<?php
namespace TYPO3\Flow\Security\Aspect;

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
 * An aspect which cares for a special publishing of private resources.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PrivateResourcesPublishingAspect_Original {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Session\SessionInterface
	 * @Flow\Inject
	 */
	protected $session;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface
	 * @Flow\Inject
	 */
	protected $accessRestrictionPublisher;

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
	 * Returns the web URI to be used to publish the specified persistent resource
	 *
	 * @Flow\Around("setting(TYPO3.Flow.security.enable) && method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourceWebUri())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method, a rewritten private resource URI or FALSE on error
	 * @todo Rewrite of the resource title should be done by general string to uri rewrite function from somewhere else
	 */
	public function rewritePersistentResourceWebUriForPrivateResources(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$filename = $resource->getFilename();
		$configuration = $resource->getPublishingConfiguration();

		if ($configuration === NULL || ($configuration instanceof \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$result = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$privatePathSegment = $this->session->getID();
			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') $privatePathSegment = \TYPO3\Flow\Utility\Files::concatenatePaths(array($privatePathSegment, $allowedRoles[0]));

			$rewrittenFilename = ($filename === '' || $filename === NULL) ? '' : '/' . preg_replace(array('/ /', '/_/', '/[^-a-z0-9.]/i'), array('-', '-', ''), $filename);
			$result = \TYPO3\Flow\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesBaseUri(), 'Persistent/', $privatePathSegment, $resource->getResourcePointer()->getHash() . $rewrittenFilename));
		}

		return $result;
	}

	/**
	 * Returns the publish path and filename to be used to publish the specified persistent resource
	 *
	 * @Flow\Around("method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourcePublishPathAndFilename()) && setting(TYPO3.Flow.security.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResources(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$configuration = $resource->getPublishingConfiguration();
		$returnFilename = $joinPoint->getMethodArgument('returnFilename');

		if ($configuration === NULL || ($configuration instanceof \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$publishingPath = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesPublishingPath(), 'Persistent/', $this->session->getID())) . '/';
			$filename = $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();

			\TYPO3\Flow\Utility\Files::createDirectoryRecursively($publishingPath);
			$this->accessRestrictionPublisher->publishAccessRestrictionsForPath($publishingPath);

			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {

				foreach ($allowedRoles as $role) {
					$roleDirectory = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'PrivateResourcePublishing/', $role));
					\TYPO3\Flow\Utility\Files::createDirectoryRecursively($roleDirectory);

					if (file_exists($publishingPath . $role)) {
						if (\TYPO3\Flow\Utility\Files::is_link(\TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role))) && (realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role))) === $roleDirectory)) {
							continue;
						}
						unlink($publishingPath . $role);
						symlink($roleDirectory, \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					} else {
						symlink($roleDirectory, \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					}
				}
				$publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $allowedRoles[0])) . '/';
			}

			if ($returnFilename === TRUE) $publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $filename));
		}

		return $publishingPath;
	}

	/**
	 * Unpublishes a private resource from all private user directories
	 *
	 * @Flow\After("method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->unpublishPersistentResource()) && setting(TYPO3.Flow.security.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 * @todo implement this method
	 */
	public function unpublishPrivateResource(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return FALSE;
	}
}

namespace TYPO3\Flow\Security\Aspect;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which cares for a special publishing of private resources.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 * @\TYPO3\Flow\Annotations\Aspect
 */
class PrivateResourcesPublishingAspect extends PrivateResourcesPublishingAspect_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', $this);
		if ('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', $propertyName, 'transient')) continue;
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
		$securityContext_reference = &$this->securityContext;
		$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Context');
		if ($this->securityContext === NULL) {
			$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('48836470c14129ade5f39e28c4816673', $securityContext_reference);
			if ($this->securityContext === NULL) {
				$this->securityContext = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('48836470c14129ade5f39e28c4816673',  $securityContext_reference, 'TYPO3\Flow\Security\Context', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Context'); });
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
		$environment_reference = &$this->environment;
		$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Utility\Environment');
		if ($this->environment === NULL) {
			$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('d7473831479e64d04a54de9aedcdc371', $environment_reference);
			if ($this->environment === NULL) {
				$this->environment = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('d7473831479e64d04a54de9aedcdc371',  $environment_reference, 'TYPO3\Flow\Utility\Environment', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment'); });
			}
		}
		$accessRestrictionPublisher_reference = &$this->accessRestrictionPublisher;
		$this->accessRestrictionPublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface');
		if ($this->accessRestrictionPublisher === NULL) {
			$this->accessRestrictionPublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('94b1de7ab92c81096bbb712c36c56285', $accessRestrictionPublisher_reference);
			if ($this->accessRestrictionPublisher === NULL) {
				$this->accessRestrictionPublisher = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('94b1de7ab92c81096bbb712c36c56285',  $accessRestrictionPublisher_reference, 'TYPO3\Flow\Security\Authorization\Resource\Apache2AccessRestrictionPublisher', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface'); });
			}
		}
	}
}
#