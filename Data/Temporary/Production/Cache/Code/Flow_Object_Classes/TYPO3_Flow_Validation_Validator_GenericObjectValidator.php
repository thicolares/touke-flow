<?php
namespace TYPO3\Flow\Validation\Validator;

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
 * A generic object validator which allows for specifying property validators.
 *
 * @api
 */
class GenericObjectValidator_Original extends AbstractValidator implements ObjectValidatorInterface {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $validatedInstancesContainer;

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer) {
		$this->validatedInstancesContainer = $validatedInstancesContainer;
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\Flow\Error\Result();
		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			if (!is_object($value)) {
				$this->addError('Object expected, %1$s given.', 1241099149, array(gettype($value)));
			} elseif ($this->isValidatedAlready($value) === FALSE) {
				$this->isValid($value);
			}
		}
		return $this->result;
	}

	/**
	 * Checks if the given value is valid according to the property validators.
	 *
	 * @param mixed $object The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($object) {
		foreach ($this->propertyValidators as $propertyName => $validators) {
			$propertyValue = $this->getPropertyValue($object, $propertyName);
			$this->checkProperty($propertyValue, $validators, $propertyName);
		}
		return;
	}

	/**
	 * @param object $object
	 * @return boolean
	 */
	protected function isValidatedAlready($object) {
		if ($this->validatedInstancesContainer === NULL) {
			$this->validatedInstancesContainer = new \SplObjectStorage();
		}
		if ($this->validatedInstancesContainer->contains($object)) {
			return TRUE;
		} else {
			$this->validatedInstancesContainer->attach($object);
			return FALSE;
		}
	}

	/**
	 * Load the property value to be used for validation.
	 *
	 * In case the object is a doctrine proxy, we need to load the real instance first.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	protected function getPropertyValue($object, $propertyName) {
		if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
			$object->__load();
		}

		if (\TYPO3\Flow\Reflection\ObjectAccess::isPropertyGettable($object, $propertyName)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName);
		} else {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		}
	}

	/**
	 * Checks if the specified property of the given object is valid, and adds
	 * found errors to the $messages object.
	 *
	 * @param mixed $value The value to be validated
	 * @param array $validators The validators to be called on the value
	 * @param string $propertyName Name of ther property to check
	 * @return void
	 */
	protected function checkProperty($value, $validators, $propertyName) {
		$result = NULL;
		foreach ($validators as $validator) {
			if ($validator instanceof ObjectValidatorInterface) {
				$validator->setValidatedInstancesContainer($this->validatedInstancesContainer);
			}
			$currentResult = $validator->validate($value);
			if ($currentResult->hasMessages()) {
				if ($result == NULL) {
					$result = $currentResult;
				} else {
					$result->merge($currentResult);
				}
			}
		}
		if ($result !== NULL) {
			$this->result->forProperty($propertyName)->merge($result);
		}
	}


	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @api
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @api
	 */
	public function addPropertyValidator($propertyName, \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator) {
		if (!isset($this->propertyValidators[$propertyName])) {
			$this->propertyValidators[$propertyName] = new \SplObjectStorage();
		}
		$this->propertyValidators[$propertyName]->attach($validator);
	}

	/**
	 * Returns all property validators - or only validators of the specified property
	 *
	 * @param string $propertyName Name of the property to return validators for
	 * @return array An array of validators
	 */
	public function getPropertyValidators($propertyName = NULL) {
		if ($propertyName !== NULL) {
			return (isset($this->propertyValidators[$propertyName])) ? $this->propertyValidators[$propertyName] : array();
		} else {
			return $this->propertyValidators;
		}
	}
}

namespace TYPO3\Flow\Validation\Validator;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A generic object validator which allows for specifying property validators.
 */
class GenericObjectValidator extends GenericObjectValidator_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param array $options Options for the validator
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if unsupported options are found
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = array (
);
		call_user_func_array('parent::__construct', $arguments);
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
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Validation\Validator\GenericObjectValidator');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Validation\Validator\GenericObjectValidator', $propertyName, 'transient')) continue;
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