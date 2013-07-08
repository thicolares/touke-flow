<?php
namespace TYPO3\Flow\Command;

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
use TYPO3\Flow\Core\Booting\Scripts;

/**
 * Package command controller to handle packages from CLI (create/activate/deactivate packages)
 *
 * @Flow\Scope("singleton")
 */
class PackageCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @param array $settings The Flow settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	public function injectBootstrap(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\Flow\Package\PackageManagerInterface $packageManager) {
		$this->packageManager =  $packageManager;
	}

	/**
	 * Create a new package
	 *
	 * This command creates a new package which contains only the mandatory
	 * directories and files.
	 *
	 * @Flow\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.kickstart:kickstart:package
	 */
	public function createCommand($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->outputLine('The package key "%s" is not valid.', array($packageKey));
			$this->quit(1);
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('The package "%s" already exists.', array($packageKey));
			$this->quit(1);
		}
		$package = $this->packageManager->createPackage($packageKey);
		$this->outputLine('Created new package "' . $packageKey . '" at "' . $package->getPackagePath() . '".');
	}

	/**
	 * Delete an existing package
	 *
	 * This command deletes an existing package identified by the package key.
	 *
	 * @Flow\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function deleteCommand($packageKey) {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('The package "%s" does not exist.', array($packageKey));
			$this->quit(1);
		}
		$this->packageManager->deletePackage($packageKey);
		$this->outputLine('Deleted package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow:cache:flush', $this->settings, FALSE);
		$this->sendAndExit(0);
	}

	/**
	 * Activate an available package
	 *
	 * This command activates an existing, but currently inactive package.
	 *
	 * @Flow\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.flow:package:deactivate
	 */
	public function activateCommand($packageKey) {
		if ($this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" is already active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->activatePackage($packageKey);
		$this->outputLine('Activated package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow:cache:flush', $this->settings, FALSE);
		$this->sendAndExit(0);
	}

	/**
	 * Deactivate a package
	 *
	 * This command deactivates a currently active package.
	 *
	 * @Flow\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.flow:package:activate
	 */
	public function deactivateCommand($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" was not active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->deactivatePackage($packageKey);
		$this->outputLine('Deactivated package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow:cache:flush', $this->settings, FALSE);
		$this->sendAndExit(0);
	}

	/**
	 * List available packages
	 *
	 * Lists all locally available packages. Displays the package key, version and
	 * package title and its state – active or inactive.
	 *
	 * @return string The list of packages
	 * @see typo3.flow:package:activate
	 * @see typo3.flow:package:deactivate
	 */
	public function listCommand() {
		$activePackages = array();
		$inactivePackages = array();
		$frozenPackages = array();
		$longestPackageKey = 0;
		$freezeSupported = $this->bootstrap->getContext()->isDevelopment();

		foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
			if (strlen($packageKey) > $longestPackageKey) {
				$longestPackageKey = strlen($packageKey);
			}
			if ($this->packageManager->isPackageActive($packageKey)) {
				$activePackages[$packageKey] = $package;
			} else {
				$inactivePackages[$packageKey] = $package;
			}
			if ($this->packageManager->isPackageFrozen($packageKey)) {
				$frozenPackages[$packageKey] = $package;
			}
		}

		ksort($activePackages);
		ksort($inactivePackages);

		$this->outputLine('ACTIVE PACKAGES:');
		foreach ($activePackages as $package) {
			$packageMetaData = $package->getPackageMetaData();
			$frozenState = ($freezeSupported && isset($frozenPackages[$package->getPackageKey()]) ? '* ' : '  ' );
			$this->outputLine(' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . $frozenState . str_pad($packageMetaData->getVersion(), 15));
		}

		if (count($inactivePackages) > 0) {
			$this->outputLine();
			$this->outputLine('INACTIVE PACKAGES:');
			foreach ($inactivePackages as $package) {
				$frozenState = (isset($frozenPackages[$package->getPackageKey()]) ? '* ' : '  ' );
				$packageMetaData = $package->getPackageMetaData();
				$this->outputLine(' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . $frozenState . str_pad($packageMetaData->getVersion(), 15));
			}
		}

		if (count($frozenPackages) > 0 && $freezeSupported) {
			$this->outputLine();
			$this->outputLine(' * frozen package');
		}
	}

	/**
	 * Freeze a package
	 *
	 * This function marks a package as <b>frozen</b> in order to improve performance
	 * in a development context. While a package is frozen, any modification of files
	 * within that package won't be tracked and can lead to unexpected behavior.
	 *
	 * File monitoring won't consider the given package. Further more, reflection
	 * data for classes contained in the package is cached persistently and loaded
	 * directly on the first request after caches have been flushed. The precompiled
	 * reflection data is stored in the <b>Configuration</b> directory of the
	 * respective package.
	 *
	 * By specifying <b>all</b> as a package key, all currently frozen packages are
	 * frozen (the default).
	 *
	 * @param string $packageKey Key of the package to freeze
	 * @return void
	 * @see typo3.flow:package:unfreeze
	 * @see typo3.flow:package:refreeze
	 */
	public function freezeCommand($packageKey = 'all') {
		if (!$this->bootstrap->getContext()->isDevelopment()) {
			$this->outputLine('Package freezing is only supported in Development context.');
			$this->quit(3);
		}

		$packagesToFreeze = array();

		if ($packageKey === 'all') {
			foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
				if (!$this->packageManager->isPackageFrozen($packageKey)) {
					$packagesToFreeze[] = $packageKey;
				}
			}
			if ($packagesToFreeze === array()) {
				$this->outputLine('Nothing to do, all active packages were already frozen.');
				$this->quit(0);
			}
		} elseif ($packageKey === 'blackberry') {
			$this->outputLine('http://bit.ly/freeze-blackberry');
			$this->quit(42);
		} else {
			if (!$this->packageManager->isPackageActive($packageKey)) {
				if ($this->packageManager->isPackageAvailable($packageKey)) {
					$this->outputLine('Package "%s" is not active and thus cannot be frozen.', array($packageKey));
					$this->quit(1);
				} else {
					$this->outputLine('Package "%s" is not available.', array($packageKey));
					$this->quit(2);
				}
			}

			if ($this->packageManager->isPackageFrozen($packageKey)) {
				$this->outputLine('Package "%s" was already frozen.', array($packageKey));
				$this->quit(0);
			}

			$packagesToFreeze = array($packageKey);
		}

		foreach ($packagesToFreeze as $packageKey) {
			$this->packageManager->freezePackage($packageKey);
			$this->outputLine('Froze package "%s".', array($packageKey));
		}
	}

	/**
	 * Unfreeze a package
	 *
	 * Unfreezes a previously frozen package. On the next request, this package will
	 * be considered again by the file monitoring and related services – if they are
	 * enabled in the current context.
	 *
	 * By specifying <b>all</b> as a package key, all currently frozen packages are
	 * unfrozen (the default).
	 *
	 * @param string $packageKey Key of the package to unfreeze, or 'all'
	 * @return void
	 * @see typo3.flow:package:freeze
	 * @see typo3.flow:cache:flush
	 */
	public function unfreezeCommand($packageKey = 'all') {
		if (!$this->bootstrap->getContext()->isDevelopment()) {
			$this->outputLine('Package freezing is only supported in Development context.');
			$this->quit(3);
		}

		$packagesToUnfreeze = array();

		if ($packageKey === 'all') {
			foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
				if ($this->packageManager->isPackageFrozen($packageKey)) {
					$packagesToUnfreeze[] = $packageKey;
				}
			}
			if ($packagesToUnfreeze === array()) {
				$this->outputLine('Nothing to do, no packages were frozen.');
				$this->quit(0);
			}
		} else {
			if ($packageKey === NULL) {
				$this->outputLine('You must specify a package to unfreeze.');
				$this->quit(1);
			}

			if (!$this->packageManager->isPackageAvailable($packageKey)) {
				$this->outputLine('Package "%s" is not available.', array($packageKey));
				$this->quit(2);
			}
			if (!$this->packageManager->isPackageFrozen($packageKey)) {
				$this->outputLine('Package "%s" was not frozen.', array($packageKey));
				$this->quit(0);
			}
			$packagesToUnfreeze = array($packageKey);
		}

		foreach ($packagesToUnfreeze as $packageKey) {
			$this->packageManager->unfreezePackage($packageKey);
			$this->outputLine('Unfroze package "%s".', array($packageKey));
		}
	}

	/**
	 * Refreeze a package
	 *
	 * Refreezes a currently frozen package: all precompiled information is removed
	 * and file monitoring will consider the package exactly once, on the next
	 * request. After that request, the package remains frozen again, just with the
	 * updated data.
	 *
	 * By specifying <b>all</b> as a package key, all currently frozen packages are
	 * refrozen (the default).
	 *
	 * @param string $packageKey Key of the package to refreeze, or 'all'
	 * @return void
	 * @see typo3.flow:package:freeze
	 * @see typo3.flow:cache:flush
	 */
	public function refreezeCommand($packageKey = 'all') {
		if (!$this->bootstrap->getContext()->isDevelopment()) {
			$this->outputLine('Package freezing is only supported in Development context.');
			$this->quit(3);
		}

		$packagesToRefreeze = array();

		if ($packageKey === 'all') {
			foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
				if ($this->packageManager->isPackageFrozen($packageKey)) {
					$packagesToRefreeze[] = $packageKey;
				}
			}
			if ($packagesToRefreeze === array()) {
				$this->outputLine('Nothing to do, no packages were frozen.');
				$this->quit(0);
			}
		} else {
			if ($packageKey === NULL) {
				$this->outputLine('You must specify a package to refreeze.');
				$this->quit(1);
			}

			if (!$this->packageManager->isPackageAvailable($packageKey)) {
				$this->outputLine('Package "%s" is not available.', array($packageKey));
				$this->quit(2);
			}
			if (!$this->packageManager->isPackageFrozen($packageKey)) {
				$this->outputLine('Package "%s" was not frozen.', array($packageKey));
				$this->quit(0);
			}
			$packagesToRefreeze = array($packageKey);
		}

		foreach ($packagesToRefreeze as $packageKey) {
			$this->packageManager->refreezePackage($packageKey);
			$this->outputLine('Refroze package "%s".', array($packageKey));
		}

		Scripts::executeCommand('typo3.flow:cache:flush', $this->settings, FALSE);
		$this->sendAndExit(0);
	}
}

namespace TYPO3\Flow\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Package command controller to handle packages from CLI (create/activate/deactivate packages)
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class PackageCommandController extends PackageCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Command\PackageCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\PackageCommandController', $this);
		parent::__construct();
		if ('TYPO3\Flow\Command\PackageCommandController' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Command\PackageCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\PackageCommandController', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Command\PackageCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Command\PackageCommandController', $propertyName, 'transient')) continue;
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
		$this->injectBootstrap(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap'));
		$this->injectPackageManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
	}
}
#