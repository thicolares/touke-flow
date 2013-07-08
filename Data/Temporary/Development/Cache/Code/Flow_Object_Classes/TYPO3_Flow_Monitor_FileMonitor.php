<?php
namespace TYPO3\Flow\Monitor;

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
 * A monitor which detects changes in directories or files
 *
 * @api
 */
class FileMonitor_Original {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface
	 */
	protected $changeDetectionStrategy;

	/**
	 * @var \TYPO3\Flow\SignalSlot\Dispatcher
	 */
	protected $signalDispatcher;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var array
	 */
	protected $monitoredFiles = array();

	/**
	 * @var array
	 */
	protected $monitoredDirectories = array();

	/**
	 * @var array
	 */
	protected $directoriesAndFiles = array();

	/**
	 * If the directories changed and therefore need to be cached
	 * @var boolean
	 */
	protected $directoriesChanged = FALSE;

	/**
	 * Constructs this file monitor
	 *
	 * @param string $identifier Name of this specific file monitor - will be used in the signals emitted by this monitor.
	 * @api
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Injects the Change Detection Strategy
	 *
	 * @param \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
	 * @return void
	 */
	public function injectChangeDetectionStrategy(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy) {
		$this->changeDetectionStrategy = $changeDetectionStrategy;
		$this->changeDetectionStrategy->setFileMonitor($this);
	}

	/**
	 * Injects the Singal Slot Dispatcher because classes of the Monitor subpackage cannot be proxied by the AOP
	 * framework because it is not initialized at the time the monitoring is used.
	 *
	 * @param \TYPO3\Flow\SignalSlot\Dispatcher $signalDispatcher The Signal Slot Dispatcher
	 * @return void
	 */
	public function injectSignalDispatcher(\TYPO3\Flow\SignalSlot\Dispatcher $signalDispatcher) {
		$this->signalDispatcher = $signalDispatcher;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects the Flow_Monitor cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this monitor
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has($this->identifier . '_directoriesAndFiles')) {
			$this->directoriesAndFiles = $this->cache->get($this->identifier . '_directoriesAndFiles');
		}
	}

	/**
	 * Returns the identifier of this monitor
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Adds the specified file to the list of files to be monitored.
	 * The file in question does not necessarily have to exist.
	 *
	 * @param string $pathAndFilename Absolute path and filename of the file to monitor
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function monitorFile($pathAndFilename) {
		if (!is_string($pathAndFilename)) throw new \InvalidArgumentException('String expected, ' . gettype($pathAndFilename), ' given.', 1231171809);
		$pathAndFilename = \TYPO3\Flow\Utility\Files::getUnixStylePath($pathAndFilename);
		if (array_search($pathAndFilename, $this->monitoredFiles) === FALSE) {
			$this->monitoredFiles[] = $pathAndFilename;
		}
	}

	/**
	 * Adds the specified directory to the list of directories to be monitored.
	 * All files in these directories will be monitored too.
	 *
	 * @param string $path Absolute path of the directory to monitor
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function monitorDirectory($path) {
		if (!is_string($path)) throw new \InvalidArgumentException('String expected, ' . gettype($path), ' given.', 1231171810);
		$path = rtrim(\TYPO3\Flow\Utility\Files::getUnixStylePath($path), '/');
		if (array_search($path, $this->monitoredDirectories) === FALSE) {
			$this->monitoredDirectories[] = $path;
		}
	}

	/**
	 * Returns a list of all monitored files
	 *
	 * @return array A list of paths and filenames of monitored files
	 * @api
	 */
	public function getMonitoredFiles() {
		return $this->monitoredFiles;
	}

	/**
	 * Returns a list of all monitored directories
	 *
	 * @return array A list of paths of monitored directories
	 * @api
	 */
	public function getMonitoredDirectories() {
		return $this->monitoredDirectories;
	}

	/**
	 * Detects changes of the files and directories to be monitored and emits signals
	 * accordingly.
	 *
	 * @return void
	 * @api
	 */
	public function detectChanges() {
		$changedDirectories = array();
		$changedFiles = $this->detectChangedFiles($this->monitoredFiles);

		foreach ($this->monitoredDirectories as $path) {
			if (!isset($this->directoriesAndFiles[$path])) {
				$this->directoriesAndFiles[$path] = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($path);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED;
			}
		}

		foreach ($this->directoriesAndFiles as $path => $pathAndFilenames) {
			try {
				$currentSubDirectoriesAndFiles = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($path);
				if ($currentSubDirectoriesAndFiles != $pathAndFilenames) {
					$pathAndFilenames = array_unique(array_merge($currentSubDirectoriesAndFiles, $pathAndFilenames));
				}
				$changedFiles = array_merge($changedFiles, $this->detectChangedFiles($pathAndFilenames));
			} catch (\TYPO3\Flow\Utility\Exception $exception) {
				unset($this->directoriesAndFiles[$path]);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED;
			}
		}

		if (count($changedFiles) > 0) {
			$this->emitFilesHaveChanged($this->identifier, $changedFiles);
		}
		if (count($changedDirectories) > 0) {
			$this->emitDirectoriesHaveChanged($this->identifier, $changedDirectories);
		}
		if (count($changedFiles) > 0 || count($changedDirectories) > 0) {
			$this->systemLogger->log(sprintf('File Monitor "%s" detected %s changed files and %s changed directories.', $this->identifier, count($changedFiles), count($changedDirectories)), LOG_INFO);
		}
	}

	/**
	 * Detects changes in the given list of files and emits signals if necessary.
	 *
	 * @param array $pathAndFilenames A list of full path and filenames of files to check
	 * @return array An array of changed files (key = path and filenmae) and their status (value)
	 */
	protected function detectChangedFiles(array $pathAndFilenames) {
		$changedFiles = array();
		foreach ($pathAndFilenames as $pathAndFilename) {
			$status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
			if ($status !== \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
				$changedFiles[$pathAndFilename] = $status;
			}
		}
		return $changedFiles;
	}

	/**
	 * Signalizes that the specified file has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedFiles An array of changed files (key = path and filename) and their status (value)
	 * @return void
	 * @Flow\Signal
	 * @api
	 */
	protected function emitFilesHaveChanged($monitorIdentifier, array $changedFiles) {
		$this->signalDispatcher->dispatch('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', array($monitorIdentifier, $changedFiles));
	}

	/**
	 * Signalizes that the specified directory has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedDirectories An array of changed directories (key = path) and their status (value)
	 * @return void
	 * @Flow\Signal
	 * @api
	 */
	protected function emitDirectoriesHaveChanged($monitorIdentifier, array $changedDirectories) {
		$this->signalDispatcher->dispatch('TYPO3\Flow\Monitor\FileMonitor', 'directoriesHaveChanged', array($monitorIdentifier, $changedDirectories));
	}

	/**
	 * Caches the directories and their files
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->directoriesChanged === TRUE) {
			$this->cache->set($this->identifier . '_directoriesAndFiles', $this->directoriesAndFiles);
		}
		$this->changeDetectionStrategy->shutdownObject();
	}
}
namespace TYPO3\Flow\Monitor;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A monitor which detects changes in directories or files
 */
class FileMonitor extends FileMonitor_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param string $identifier Name of this specific file monitor - will be used in the signals emitted by this monitor.
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $identifier in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Monitor\FileMonitor' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);

		\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, 'shutdownObject');
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

		\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, 'shutdownObject');
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Monitor\FileMonitor');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Monitor\FileMonitor', $propertyName, 'transient')) continue;
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
		$this->injectCache(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Monitor'));
		$this->injectChangeDetectionStrategy(new \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy());
		$this->injectSignalDispatcher(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\SignalSlot\Dispatcher'));
		$this->injectSystemLogger(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'));
	}
}
#