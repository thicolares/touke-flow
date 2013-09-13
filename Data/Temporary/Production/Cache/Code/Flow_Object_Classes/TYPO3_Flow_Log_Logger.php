<?php
namespace TYPO3\Flow\Log;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * The default logger of the Flow framework
 *
 * @api
 */
class Logger_Original implements \TYPO3\Flow\Log\SystemLoggerInterface, \TYPO3\Flow\Log\SecurityLoggerInterface {

	/**
	 * @var \SplObjectStorage
	 */
	protected $backends;

	/**
	 * Constructs the logger
	 *
	 */
	public function __construct() {
		$this->backends = new \SplObjectStorage();
	}

	/**
	 * Sets the given backend as the only backend for this Logger.
	 *
	 * This method allows for conveniently injecting a backend through some Objects.yaml configuration.
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function setBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend) {
		$this->backends = new \SplObjectStorage();
		$this->backends->attach($backend);
	}

	/**
	 * Adds the backend to which the logger sends the logging data
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function addBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend) {
		$this->backends->attach($backend);
		$backend->open();
	}

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend The backend to remove
	 * @return void
	 * @throws \TYPO3\Flow\Log\Exception\NoSuchBackendException if the given backend is unknown to this logger
	 * @api
	 */
	public function removeBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend) {
		if (!$this->backends->contains($backend)) throw new \TYPO3\Flow\Log\Exception\NoSuchBackendException('Backend is unknown to this logger.', 1229430381);
		$backend->close();
		$this->backends->detach($backend);
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @api
	 */
	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if ($packageKey === NULL) {
			$backtrace = debug_backtrace(FALSE);
			$className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : NULL;
			$methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : NULL;
			$explodedClassName = explode('\\', $className);
				// FIXME: This is not really the package key:
			$packageKey = isset($explodedClassName[1]) ? $explodedClassName[1] : '';
		}
		foreach ($this->backends as $backend) {
			$backend->append($message, $severity, $additionalData, $packageKey, $className, $methodName);
		}
	}

	/**
	 * Writes information about the given exception into the log.
	 *
	 * @param \Exception $exception The exception to log
	 * @param array $additionalData Additional data to log
	 * @return void
	 * @api
	 */
	public function logException(\Exception $exception, array $additionalData = array()) {
		$backTrace = $exception->getTrace();
		$className = isset($backTrace[0]['class']) ? $backTrace[0]['class'] : '?';
		$methodName = isset($backTrace[0]['function']) ? $backTrace[0]['function'] : '?';
		$message = $this->getExceptionLogMessage($exception);

		if ($exception->getPrevious() !== NULL) {
			$additionalData['previousException'] = $this->getExceptionLogMessage($exception->getPrevious());
		}

		$explodedClassName = explode('\\', $className);
			// FIXME: This is not really the package key:
		$packageKey = (isset($explodedClassName[1])) ? $explodedClassName[1] : NULL;

		if (!file_exists(FLOW_PATH_DATA . 'Logs/Exceptions')) {
			mkdir(FLOW_PATH_DATA . 'Logs/Exceptions');
		}
		if (file_exists(FLOW_PATH_DATA . 'Logs/Exceptions') && is_dir(FLOW_PATH_DATA . 'Logs/Exceptions') && is_writable(FLOW_PATH_DATA . 'Logs/Exceptions')) {
			$referenceCode = ($exception instanceof \TYPO3\Flow\Exception) ? $exception->getReferenceCode() : date('YmdHis' , $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
			$exceptionDumpPathAndFilename = FLOW_PATH_DATA . 'Logs/Exceptions/' . $referenceCode . '.txt';
			file_put_contents($exceptionDumpPathAndFilename, $message . PHP_EOL . PHP_EOL . \TYPO3\Flow\Error\Debugger::getBacktraceCode($backTrace, FALSE, TRUE));
			$message .= ' - See also: ' . basename($exceptionDumpPathAndFilename);
		} else {
			$this->log(sprintf('Could not write exception backtrace into %s because the directory could not be created or is not writable.', FLOW_PATH_DATA . 'Logs/Exceptions/'), LOG_WARNING, array(), 'Flow', __CLASS__, __FUNCTION__);
		}

		$this->log($message, LOG_CRIT, $additionalData, $packageKey, $className, $methodName);
	}

	/**
	 * @param \Exception $exception
	 * @return string
	 */
	protected function getExceptionLogMessage(\Exception $exception) {
		$exceptionCodeNumber = ($exception->getCode() > 0) ? ' #' . $exception->getCode() : '';
		$backTrace = $exception->getTrace();
		$line = isset($backTrace[0]['line']) ? ' in line ' . $backTrace[0]['line'] . ' of ' . $backTrace[0]['file'] : '';
		return 'Uncaught exception' . $exceptionCodeNumber . $line . ': ' . $exception->getMessage() ;
	}

	/**
	 * Cleanly closes all registered backends before destructing this Logger
	 *
	 * @return void
	 */
	public function shutdownObject() {
		foreach ($this->backends as $backend) {
			$backend->close();
		}
	}
}
namespace TYPO3\Flow\Log;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * The default logger of the Flow framework
 */
class Logger extends Logger_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();

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
				$result = NULL;

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Log\Logger');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Log\Logger', $propertyName, 'transient')) continue;
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
}
#