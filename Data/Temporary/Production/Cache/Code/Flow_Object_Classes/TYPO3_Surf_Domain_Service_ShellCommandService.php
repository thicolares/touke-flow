<?php
namespace TYPO3\Surf\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * A shell command service
 *
 */
class ShellCommandService_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Execute a shell command (locally or remote depending on the node hostname)
	 *
	 * @param mixed $command The shell command to execute, either string or array of commands
	 * @param \TYPO3\Surf\Domain\Model\Node $node Node to execute command against, NULL means localhost
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param boolean $ignoreErrors If this command should ignore exit codes unequeal zero
	 * @param boolean $logOutput TRUE if the output of the command should be logged
	 * @return mixed The output of the shell command or FALSE if the command returned a non-zero exit code and $ignoreErrors was enabled.
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function execute($command, Node $node, Deployment $deployment, $ignoreErrors = FALSE, $logOutput = TRUE) {
		if ($node === NULL || $node->isLocalhost()) {
			list($exitCode, $returnedOutput) = $this->executeLocalCommand($command, $deployment, $logOutput);
		} else {
			list($exitCode, $returnedOutput) = $this->executeRemoteCommand($command, $node, $deployment, $logOutput);
		}
		if ($ignoreErrors !== TRUE && $exitCode !== 0) {
          print "\n" . $command . "\n";
			$deployment->getLogger()->log(rtrim($returnedOutput), LOG_WARNING);
			throw new \TYPO3\Surf\Exception\TaskExecutionException('Command returned non-zero return code: ' . $exitCode, 1311007746);
		}
		return ($exitCode === 0 ? $returnedOutput : FALSE);
	}

	/**
	 * Simulate a command by just outputting what would be executed
	 *
	 * @param string $command
	 * @param Node $node
	 * @param Deployment $deployment
	 * @return bool
	 */
	public function simulate($command, Node $node, Deployment $deployment) {
		if ($node === NULL || $node->isLocalhost()) {
			$command = $this->prepareCommand($command);
			$deployment->getLogger()->log('... (localhost): "' . $command . '"', LOG_DEBUG);
		} else {
			$command = $this->prepareCommand($command);
			$deployment->getLogger()->log('... $' . $node->getName() . ': "' . $command . '"', LOG_DEBUG);
		}
		return TRUE;
	}

	/**
	 * Execute or simulate a command (if the deployment is in dry run mode)
	 *
	 * @param string $command
	 * @param Node $node
	 * @param Deployment $deployment
	 * @param boolean $ignoreErrors
	 * @param boolean $logOutput TRUE if the output of the command should be logged
	 * @return boolean|mixed
	 */
	public function executeOrSimulate($command, Node $node, Deployment $deployment, $ignoreErrors = FALSE, $logOutput = TRUE) {
		if (!$deployment->isDryRun()) {
			return $this->execute($command, $node, $deployment, $ignoreErrors, $logOutput);
		} else {
			return $this->simulate($command, $node, $deployment);
		}
	}

	/**
	 * Execute a shell command locally
	 *
	 * @param mixed $command
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param boolean $logOutput TRUE if the output of the command should be logged
	 * @return array
	 */
	protected function executeLocalCommand($command, Deployment $deployment, $logOutput = TRUE) {
		$command = $this->prepareCommand($command);
		$deployment->getLogger()->log('(localhost): "' . $command . '"', LOG_DEBUG);

		return $this->executeProcess($deployment, $command, $logOutput, '> ');
	}


	/**
	 * Execute a shell command via SSH
	 *
	 * @param mixed $command
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param boolean $logOutput TRUE if the output of the command should be logged
	 * @return array
	 */
	protected function executeRemoteCommand($command, Node $node, Deployment $deployment, $logOutput = TRUE) {
		$command = $this->prepareCommand($command);
		$deployment->getLogger()->log('$' . $node->getName() . ': "' . $command . '"', LOG_DEBUG);

		if ($node->hasOption('remoteCommandExecutionHandler')) {
			$remoteCommandExecutionHandler = $node->getOption('remoteCommandExecutionHandler');
			/** @var $remoteCommandExecutionHandler callable */
			return $remoteCommandExecutionHandler($this, $command, $node, $deployment, $logOutput);
		}

		$username = $node->hasOption('username') ? $node->getOption('username') : NULL;
		if (!empty($username)) {
			$username = $username . '@';
		}
		$hostname = $node->getHostname();

			// TODO Get SSH options from node or deployment
		$sshOptions = array('-A');
		if ($node->hasOption('port')) {
			$sshOptions[] = '-p ' . escapeshellarg($node->getOption('port'));
		}
		if ($node->hasOption('password')) {
			$sshOptions[] = '-o PubkeyAuthentication=no';
		}

		$sshCommand = 'ssh ' . implode(' ', $sshOptions) . ' ' . escapeshellarg($username . $hostname) . ' ' . escapeshellarg($command) . ' 2>&1';

		if ($node->hasOption('password')) {
			$surfPackage = $this->packageManager->getPackage('TYPO3.Surf');
			$passwordSshLoginScriptPathAndFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($surfPackage->getResourcesPath(), 'Private/Scripts/PasswordSshLogin.expect'));
			$sshCommand = sprintf('expect %s %s %s', escapeshellarg($passwordSshLoginScriptPathAndFilename), escapeshellarg($node->getOption('password')), $sshCommand);
		}

		return $this->executeProcess($deployment, $sshCommand, $logOutput, '    > ');
	}

	/**
	 * Open a process with popen and process each line by logging and
	 * collecting its output.
	 *
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param string $command
	 * @param boolean $logOutput
	 * @param string $logPrefix
	 * @return array The exit code of the command and the returned output
	 */
	public function executeProcess($deployment, $command, $logOutput, $logPrefix) {
		$returnedOutput = '';
		$fp = popen($command, 'r');
		while (($line = fgets($fp)) !== FALSE) {
			if ($logOutput) {
				$deployment->getLogger()->log($logPrefix . rtrim($line), LOG_DEBUG);
			}
			$returnedOutput .= $line;
		}
		$exitCode = pclose($fp);
		return array($exitCode, trim($returnedOutput));
	}

	/**
	 * Prepare a command
	 *
	 * @param mixed $command
	 * @return string
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function prepareCommand($command) {
		if (is_string($command)) {
			return trim($command);
		} elseif (is_array($command)) {
			return implode(' && ', $command);
		} else {
			throw new \TYPO3\Surf\Exception\TaskExecutionException('Command must be string or array, ' . gettype($command) . ' given.', 1312454906);
		}
	}

}
namespace TYPO3\Surf\Domain\Service;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A shell command service
 */
class ShellCommandService extends ShellCommandService_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Surf\Domain\Service\ShellCommandService' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Domain\Service\ShellCommandService');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Domain\Service\ShellCommandService', $propertyName, 'transient')) continue;
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
		$packageManager_reference = &$this->packageManager;
		$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Package\PackageManagerInterface');
		if ($this->packageManager === NULL) {
			$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('aad0cdb65adb124cf4b4d16c5b42256c', $packageManager_reference);
			if ($this->packageManager === NULL) {
				$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('aad0cdb65adb124cf4b4d16c5b42256c',  $packageManager_reference, 'TYPO3\Flow\Package\PackageManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'); });
			}
		}
	}
}
#