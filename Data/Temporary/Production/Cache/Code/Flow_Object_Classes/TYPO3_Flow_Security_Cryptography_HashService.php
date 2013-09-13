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

/**
 * A hash service which should be used to generate and validate hashes.
 *
 * @Flow\Scope("singleton")
 */
class HashService_Original {

	/**
	 * A private, unique key used for encryption tasks
	 * @var string
	 */
	protected $encryptionKey = NULL;

	/**
	 * @var array
	 */
	protected $passwordHashingStrategies = array();

	/**
	 * @var array
	 */
	protected $strategySettings;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * Injects the settings of the package this controller belongs to.
	 *
	 * @param array $settings Settings container of the current package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->strategySettings = $settings['security']['cryptography']['hashingStrategies'];
	}

	/**
	 * Generate a hash (HMAC) for a given string
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The hash of the string
	 * @throws \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException if something else than a string was given as parameter
	 */
	public function generateHmac($string) {
		if (!is_string($string)) {
			throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
		}

		return hash_hmac('sha1', $string, $this->getEncryptionKey());
	}

	/**
	 * Appends a hash (HMAC) to a given string and returns the result
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The original string with HMAC of the string appended
	 * @see generateHmac()
	 * @todo Mark as API once it is more stable
	 */
	public function appendHmac($string) {
		$hmac = $this->generateHmac($string);
		return $string . $hmac;
	}

	/**
	 * Tests if a string $string matches the HMAC given by $hash.
	 *
	 * @param string $string The string which should be validated
	 * @param string $hmac The hash of the string
	 * @return boolean TRUE if string and hash fit together, FALSE otherwise.
	 */
	public function validateHmac($string, $hmac) {
		return ($this->generateHmac($string) === $hmac);
	}


	/**
	 * Tests if the last 40 characters of a given string $string
	 * matches the HMAC of the rest of the string and, if true,
	 * returns the string without the HMAC. In case of a HMAC
	 * validation error, an exception is thrown.
	 *
	 * @param string $string The string with the HMAC appended (in the format 'string<HMAC>')
	 * @return string the original string without the HMAC, if validation was successful
	 * @see validateHmac()
	 * @throws \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException if the given string is not well-formatted
	 * @throws \TYPO3\Flow\Security\Exception\InvalidHashException if the hash did not fit to the data.
	 * @todo Mark as API once it is more stable
	 */
	public function validateAndStripHmac($string) {
		if (!is_string($string)) {
			throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be validated for a string, but "' . gettype($string) . '" was given.', 1320829762);
		}
		if (strlen($string) < 40) {
			throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
		}
		$stringWithoutHmac = substr($string, 0, -40);
		if ($this->validateHmac($stringWithoutHmac, substr($string, -40)) !== TRUE) {
			throw new \TYPO3\Flow\Security\Exception\InvalidHashException('The given string was not appended with a valid HMAC.', 1320830018);
		}
		return $stringWithoutHmac;
	}
	/**
	 * Hash a password using the configured password hashing strategy
	 *
	 * @param string $password The cleartext password
	 * @param string $strategyIdentifier An identifier for a configured strategy, uses default strategy if not specified
	 * @return string A hashed password with salt (if used)
	 * @api
	 */
	public function hashPassword($password, $strategyIdentifier = 'default') {
		list($passwordHashingStrategy, $strategyIdentifier) = $this->getPasswordHashingStrategyAndIdentifier($strategyIdentifier, FALSE);
		$hashedPasswordAndSalt = $passwordHashingStrategy->hashPassword($password, $this->getEncryptionKey());
		return $strategyIdentifier . '=>' . $hashedPasswordAndSalt;
	}

	/**
	 * Validate a hashed password using the configured password hashing strategy
	 *
	 * @param string $password The cleartext password
	 * @param string $hashedPasswordAndSalt The hashed password with salt (if used) and an optional strategy identifier
	 * @return boolean TRUE if the given password matches the hashed password
	 * @api
	 */
	public function validatePassword($password, $hashedPasswordAndSalt) {
		$strategyIdentifier = 'default';
		if (strpos($hashedPasswordAndSalt, '=>') !== FALSE) {
			list($strategyIdentifier, $hashedPasswordAndSalt) = explode('=>', $hashedPasswordAndSalt, 2);
		}

		list($passwordHashingStrategy, ) = $this->getPasswordHashingStrategyAndIdentifier($strategyIdentifier, TRUE);
		return $passwordHashingStrategy->validatePassword($password, $hashedPasswordAndSalt, $this->getEncryptionKey());
	}

	/**
	 * Get a password hashing strategy
	 *
	 * @param string $strategyIdentifier
	 * @param boolean $validating TRUE if the password is validated, FALSE if the password is hashed
	 * @return array Array of \TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface and string
	 * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
	 */
	protected function getPasswordHashingStrategyAndIdentifier($strategyIdentifier = 'default', $validating) {
		if (isset($this->passwordHashingStrategies[$strategyIdentifier])) {
			return array($this->passwordHashingStrategies[$strategyIdentifier], $strategyIdentifier);
		}

		if ($strategyIdentifier === 'default') {
			if ($validating && isset($this->strategySettings['fallback'])) {
				$strategyIdentifier = $this->strategySettings['fallback'];
			} else {
				if (!isset($this->strategySettings['default'])) {
					throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('No default hashing strategy configured', 1320758427);
				}
				$strategyIdentifier = $this->strategySettings['default'];
			}
		}

		if (!isset($this->strategySettings[$strategyIdentifier])) {
			throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('No hashing strategy with identifier "' . $strategyIdentifier . '" configured', 1320758776);
		}
		$strategyObjectName = $this->strategySettings[$strategyIdentifier];
		$this->passwordHashingStrategies[$strategyIdentifier] = $this->objectManager->get($strategyObjectName);
		return array($this->passwordHashingStrategies[$strategyIdentifier], $strategyIdentifier);
	}

	/**
	 * @return string The configured encryption key stored in Data/Persistent/EncryptionKey
	 * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
	 */
	protected function getEncryptionKey() {
		if ($this->encryptionKey === NULL) {
			if (!file_exists(FLOW_PATH_DATA . 'Persistent/EncryptionKey')) {
				file_put_contents(FLOW_PATH_DATA . 'Persistent/EncryptionKey', bin2hex(\TYPO3\Flow\Utility\Algorithms::generateRandomBytes(96)));
			}
			$this->encryptionKey = file_get_contents(FLOW_PATH_DATA . 'Persistent/EncryptionKey');

			if ($this->encryptionKey === FALSE || $this->encryptionKey === '') {
				throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('No encryption key for the HashService was found and none could be created at "' . FLOW_PATH_DATA . 'Persistent/EncryptionKey"', 1258991855);
			}
		}

		return $this->encryptionKey;
	}

}
namespace TYPO3\Flow\Security\Cryptography;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A hash service which should be used to generate and validate hashes.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class HashService extends HashService_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Security\Cryptography\HashService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Cryptography\HashService', $this);
		if ('TYPO3\Flow\Security\Cryptography\HashService' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Security\Cryptography\HashService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Security\Cryptography\HashService', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Security\Cryptography\HashService');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Security\Cryptography\HashService', $propertyName, 'transient')) continue;
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
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
	}
}
#