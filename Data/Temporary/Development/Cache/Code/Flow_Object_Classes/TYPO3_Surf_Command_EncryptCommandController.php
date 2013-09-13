<?php
namespace TYPO3\Surf\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Encryption command controller
 */
class EncryptCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Encryption\EncryptionServiceInterface
	 */
	protected $encryptionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\DeploymentService
	 */
	protected $deploymentService;

	/**
	 * Setup encryption with a local key for the deployment system
	 *
	 * The local key should be kept secretly and could be encrypted with
	 * an optional passphrase. The name defaults to "Local.key".
	 *
	 * @param string $passphrase Passphrase for the generated key (optional)
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 */
	public function setupCommand($passphrase = NULL, $configurationPath = NULL) {
		$deploymentPath = $this->deploymentService->getDeploymentsBasePath($configurationPath);
		if (file_exists($deploymentPath . '/Keys/Local.key')) {
			$this->outputLine('Local key already exists');
			$this->quit(1);
		}
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($deploymentPath . '/Keys');
		$keyPair = $this->encryptionService->generateKeyPair($passphrase);
		$this->writeKeyPair($keyPair, $deploymentPath . '/Keys/Local.key');
		$this->outputLine('Local key generated');
	}

	/**
	 * Encrypt configuration with the local key
	 *
	 * This command scans the subdirectory of "Build/Deploy/Configuration" for configuration
	 * files that should be encrypted. An optional deployment name restricts this operation to configuration
	 * files of a specific deployment (e.g. "Build/Deploy/Configuration/Staging").
	 *
	 * Only .yaml files with a header of "#!ENCRYPT" are encrypted.
	 *
	 * @param string $deploymentName Optional deployment name to selectively encrypt the configuration
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 * @see typo3.surf:encrypt:open
	 */
	public function sealCommand($deploymentName = '', $configurationPath = NULL) {
		if ($deploymentName !== '') {
			$deployment = $this->deploymentService->getDeployment($deploymentName, $configurationPath);
			$deploymentConfigurationPath = $deployment->getDeploymentConfigurationPath() . '/';
			$deploymentBasePath = $deployment->getDeploymentBasePath();
		} else {
			$deploymentBasePath = $this->deploymentService->getDeploymentsBasePath($configurationPath);
			$deploymentConfigurationPath = $deploymentBasePath;
		}

		$keyPair = $this->readKeyPair($deploymentBasePath . '/Keys/Local.key');
		$configurations = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($deploymentConfigurationPath, 'yaml');
		foreach ($configurations as $configuration) {
			$data = file_get_contents($configuration);
			if (strpos($data, '#!ENCRYPT') !== 0) {
				continue;
			}
			$crypted = $this->encryptionService->encryptData($data, $keyPair->getPublicKey());
			$targetFilename = $configuration . '.encrypted';
			file_put_contents($targetFilename, $crypted);
			unlink($configuration);
			$this->outputLine('Sealed ' . $targetFilename);
		}
	}

	/**
	 * Open encrypted configuration with the local key
	 *
	 * Like the seal command, this can be restricted to a specific deployment. If a passphrase
	 * was used to encrypt the local private key, it must be specified as the passphrase
	 * argument to open the configuration files.
	 *
	 * @param string $passphrase Passphrase to decrypt the local key (if encrypted)
	 * @param string $deploymentName Optional deployment name to selectively decrypt the configuration
	 * @param string $configurationPath Path for deployment configuration files
	 * @return void
	 * @see typo3.surf:encrypt:seal
	 */
	public function openCommand($passphrase = NULL, $deploymentName = '', $configurationPath = NULL) {
		if ($deploymentName !== '') {
			$deployment = $this->deploymentService->getDeployment($deploymentName, $configurationPath);
			$deploymentConfigurationPath = $deployment->getDeploymentConfigurationPath() . '/';
			$deploymentBasePath = $deployment->getDeploymentBasePath();
		} else {
			$deploymentBasePath = $this->deploymentService->getDeploymentsBasePath($configurationPath);
			$deploymentConfigurationPath = $deploymentBasePath;
		}

		$keyPair = $this->readKeyPair($deploymentBasePath . '/Keys/Local.key');
		try {
			$keyPair = $this->encryptionService->openKeyPair($keyPair, $passphrase);
		} catch(\TYPO3\Surf\Encryption\InvalidPassphraseException $exception) {
			$this->outputLine('Local key is encrypted with passphrase. Wrong or no passphrase given.');
			$this->quit(1);
		}
		$configurations = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($deploymentConfigurationPath, 'yaml.encrypted');
		foreach ($configurations as $configuration) {
			$crypted = file_get_contents($configuration);
			$data = $this->encryptionService->decryptData($crypted, $keyPair->getPrivateKey());
			$targetFilename = substr($configuration, 0, -strlen('.encrypted'));
			file_put_contents($targetFilename, $data);
			unlink($configuration);
			$this->outputLine('Opened ' . $targetFilename);
		}
	}

	/**
	 * Writes a key pair to a file
	 *
	 * @param \TYPO3\Surf\Encryption\KeyPair $keyPair
	 * @param string $filename
	 * @return void
	 */
	protected function writeKeyPair(\TYPO3\Surf\Encryption\KeyPair $keyPair, $filename) {
		$data = json_encode(array(
			'encrypted' => $keyPair->isEncrypted(),
			'privateKey' => $keyPair->getPrivateKey(),
			'publicKey' => $keyPair->getPublicKey()
		));
		file_put_contents($filename, $data);
	}

	/**
	 * Reads a key pair from a file
	 *
	 * @param string $filename
	 * @return \TYPO3\Surf\Encryption\KeyPair
	 */
	protected function readKeyPair($filename) {
		$data = file_get_contents($filename);
		$data = json_decode($data, TRUE);
		$keyPair = new \TYPO3\Surf\Encryption\KeyPair($data['privateKey'], $data['publicKey'], $data['encrypted']);
		return $keyPair;
	}

}
namespace TYPO3\Surf\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Encryption command controller
 */
class EncryptCommandController extends EncryptCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Surf\Command\EncryptCommandController' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Command\EncryptCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Command\EncryptCommandController', $propertyName, 'transient')) continue;
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
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$encryptionService_reference = &$this->encryptionService;
		$this->encryptionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Surf\Encryption\EncryptionServiceInterface');
		if ($this->encryptionService === NULL) {
			$this->encryptionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('998ca3ce114c951e04810a244025f986', $encryptionService_reference);
			if ($this->encryptionService === NULL) {
				$this->encryptionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('998ca3ce114c951e04810a244025f986',  $encryptionService_reference, 'TYPO3\Surf\Encryption\OpenSslEncryptionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Surf\Encryption\EncryptionServiceInterface'); });
			}
		}
		$this->deploymentService = new \TYPO3\Surf\Domain\Service\DeploymentService();
	}
}
#