<?php
namespace TYPO3\Flow\Security\Cryptography;

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
use TYPO3\Flow\Utility\Files;

/**
 * File based simple encrypted key service
 *
 * @Flow\Scope("singleton")
 */
class FileBasedSimpleKeyService_Original {

	/**
	 * Pattern a key name must match.
	 */
	const PATTERN_KEYNAME = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

	/**
	 * @var string
	 */
	protected $passwordHashingStrategy = 'default';

	/**
	 * @var integer
	 */
	protected $passwordGenerationLength = 8;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordHashingStrategy'])) {
			$this->passwordHashingStrategy = $settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordHashingStrategy'];
		}
		if (isset($settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordGenerationLength'])) {
			$this->passwordGenerationLength = $settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordGenerationLength'];
		}
	}

	/**
	 * Generates a new key & saves it encrypted with a hashing strategy
	 *
	 * @param string $name
	 * @return string
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function generateKey($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215474);
		}
		$password = \TYPO3\Flow\Utility\Algorithms::generateRandomString($this->passwordGenerationLength);
		$this->persistKey($name, $password);
		return $password;
	}

	/**
	 * Saves a key encrypted with a hashing strategy
	 *
	 * @param string $name
	 * @param string $password
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function storeKey($name, $password) {
		if (strlen($name) === 0) {
			throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215443);
		}
		if (strlen($password) === 0) {
			throw new \TYPO3\Flow\Security\Exception('Required password argument was empty', 1334215349);
		}
		$this->persistKey($name, $password);
	}

	/**
	 * Checks if a key exists
	 *
	 * @param string $name
	 * @return boolean
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function keyExists($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215344);
		}
		if (!file_exists($this->getKeyPathAndFilename($name))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Returns a key by its name
	 *
	 * @param string $name
	 * @return boolean
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function getKey($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215378);
		}
		$keyPathAndFilename = $this->getKeyPathAndFilename($name);
		if (!file_exists($keyPathAndFilename)) {
			throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" does not exist.', $keyPathAndFilename), 1305812921);
		}
		$key = Files::getFileContents($keyPathAndFilename);
		if ($key === FALSE) {
			throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" could not be read.', $keyPathAndFilename), 1334483163);
		}
		if (strlen($key) === 0) {
			throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" is empty.', $keyPathAndFilename), 1334483165);
		}
		return $key;
	}

	/**
	 * Persists a key to the file system
	 *
	 * @param string $name
	 * @param string $password
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	protected function persistKey($name, $password) {
		$hashedPassword = $this->hashService->hashPassword($password, $this->passwordHashingStrategy);
		$keyPathAndFilename = $this->getKeyPathAndFilename($name);
		if (!is_dir($this->getPath())) {
			Files::createDirectoryRecursively($this->getPath());
		}
		$result = file_put_contents($keyPathAndFilename, $hashedPassword);
		if ($result === FALSE) {
			throw new \TYPO3\Flow\Security\Exception(sprintf('The key could not be stored ("%s").', $keyPathAndFilename), 1305812921);
		}
	}

	/**
	 * Returns the path and filename for the key with the given name.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getKeyPathAndFilename($name) {
		return Files::concatenatePaths(array($this->getPath(), $this->checkKeyName($name)));
	}

	/**
	 * Checks if the given key name is valid amd returns it
	 * (unchanged) if yes. Otherwise it throws an exception.
	 *
	 * @param string $name
	 * @return string
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	protected function checkKeyName($name) {
		if (preg_match(self::PATTERN_KEYNAME, $name) !== 1) {
			throw new \TYPO3\Flow\Security\Exception('The key name "' . $name . '" is not valid.', 1334219077);
		}
		return $name;
	}

	/**
	 * Helper function to get the base path for key storage.
	 *
	 * @return string
	 */
	protected function getPath() {
		return Files::concatenatePaths(array(FLOW_PATH_DATA, 'Persistent', 'FileBasedSimpleKeyService'));
	}

}
namespace TYPO3\Flow\Security\Cryptography;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * File based simple encrypted key service
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class FileBasedSimpleKeyService extends FileBasedSimpleKeyService_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService', $this);
		if ('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService', $propertyName, 'transient')) continue;
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
		$hashService_reference = &$this->hashService;
		$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Security\Cryptography\HashService');
		if ($this->hashService === NULL) {
			$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('af606f3838da2ad86bf0ed2ff61be394', $hashService_reference);
			if ($this->hashService === NULL) {
				$this->hashService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('af606f3838da2ad86bf0ed2ff61be394',  $hashService_reference, 'TYPO3\Flow\Security\Cryptography\HashService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Security\Cryptography\HashService'); });
			}
		}
	}
}
#