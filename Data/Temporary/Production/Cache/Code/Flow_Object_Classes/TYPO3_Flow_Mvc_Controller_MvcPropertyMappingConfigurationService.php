<?php
namespace TYPO3\Flow\Mvc\Controller;

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
 * This is a Service which can generate a request hash and check whether the currently given arguments
 * fit to the request hash.
 *
 * It is used when forms are generated and submitted:
 * After a form has been generated, the method "generateRequestHash" is called with the names of all form fields.
 * It cleans up the array of form fields and creates another representation of it, which is then serialized and hashed.
 *
 * Both serialized form field list and the added hash form the request hash, which will be sent over the wire (as an argument __hmac).
 *
 * On the validation side, the validation happens in two steps:
 * 1) Check if the request hash is consistent (the hash value fits to the serialized string)
 * 2) Check that _all_ GET/POST parameters submitted occur inside the form field list of the request hash.
 *
 * Note: It is crucially important that a private key is computed into the hash value! This is done inside the HashService.
 *
 * @Flow\Scope("singleton")
 */
class MvcPropertyMappingConfigurationService_Original {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * Generate a request hash for a list of form fields
	 *
	 * @param array $formFieldNames Array of form fields
	 * @param string $fieldNamePrefix
	 * @return string trusted properties token
	 * @throws \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function generateTrustedPropertiesToken($formFieldNames, $fieldNamePrefix = '') {
		$formFieldArray = array();
		foreach ($formFieldNames as $formField) {
			$formFieldParts = explode('[', $formField);
			$currentPosition =& $formFieldArray;
			for ($i=0; $i < count($formFieldParts); $i++) {
				$formFieldPart = $formFieldParts[$i];
				$formFieldPart = rtrim($formFieldPart, ']');

				if (!is_array($currentPosition)) {
					throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as array, but it collides with a previous form field of the same name which declared the field as string. This is an inconsistency you need to fix inside your Fluid form. (String overridden by Array)', 1255072196);
				}

				if ($i === count($formFieldParts) - 1) {
					if (isset($currentPosition[$formFieldPart]) && is_array($currentPosition[$formFieldPart])) {
						throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as string, but it collides with a previous form field of the same name which declared the field as array. This is an inconsistency you need to fix inside your Fluid form. (Array overridden by String)', 1255072587);
					}
					// Last iteration - add a string
					if ($formFieldPart === '') {
						$currentPosition[] = 1;
					} else {
						$currentPosition[$formFieldPart] = 1;
					}
				} else {
					if ($formFieldPart === '') {
						throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is invalid. Reason: "[]" used not as last argument, but somewhere in the middle (like foo[][bar]).', 1255072832);
					}
					if (!isset($currentPosition[$formFieldPart])) {
						$currentPosition[$formFieldPart] = array();
					}
					$currentPosition =& $currentPosition[$formFieldPart];
				}
			}
		}
		if ($fieldNamePrefix !== '') {
			$formFieldArray = (isset($formFieldArray[$fieldNamePrefix]) ? $formFieldArray[$fieldNamePrefix] : array());
		}
		return $this->serializeAndHashFormFieldArray($formFieldArray);
	}

	/**
	 * Serialize and hash the form field array
	 *
	 * @param array $formFieldArray form field array to be serialized and hashed
	 * @return string Hash
	 */
	protected function serializeAndHashFormFieldArray($formFieldArray) {
		$serializedFormFieldArray = serialize($formFieldArray);
		return $this->hashService->appendHmac($serializedFormFieldArray);
	}


	/**
	 * Initialize the property mapping configuration in $controllerArguments if
	 * the trusted properties are set inside the request.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @param \TYPO3\Flow\Mvc\Controller\Arguments $controllerArguments
	 * @return void
	 */
	public function initializePropertyMappingConfigurationFromRequest(\TYPO3\Flow\Mvc\ActionRequest $request, \TYPO3\Flow\Mvc\Controller\Arguments $controllerArguments) {
		$trustedPropertiesToken = $request->getInternalArgument('__trustedProperties');
		if (!is_string($trustedPropertiesToken)) {
			return;
		}
		$serializedTrustedProperties = $this->hashService->validateAndStripHmac($trustedPropertiesToken);

		$trustedProperties = unserialize($serializedTrustedProperties);
		foreach ($trustedProperties as $propertyName => $propertyConfiguration) {
			if (!$controllerArguments->hasArgument($propertyName)) {
				continue;
			}
			$propertyMappingConfiguration = $controllerArguments->getArgument($propertyName)->getPropertyMappingConfiguration();
			$this->modifyPropertyMappingConfiguration($propertyConfiguration, $propertyMappingConfiguration);
		}
	}

	/**
	 * Modify the passed $propertyMappingConfiguration according to the $propertyConfiguration which
	 * has been generated by Fluid. In detail, if the $propertyConfiguration contains
	 * an __identity field, we allow modification of objects; else we allow creation.
	 *
	 * All other properties are specified as allowed properties.
	 *
	 * @param array $propertyConfiguration
	 * @param \TYPO3\Flow\Property\PropertyMappingConfiguration $propertyMappingConfiguration
	 * @return void
	 */
	protected function modifyPropertyMappingConfiguration($propertyConfiguration, \TYPO3\Flow\Property\PropertyMappingConfiguration $propertyMappingConfiguration) {
		if (!is_array($propertyConfiguration)) {
			return;
		}
		if (isset($propertyConfiguration['__identity'])) {
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			unset($propertyConfiguration['__identity']);
		} else {
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		}

		foreach ($propertyConfiguration as $innerKey => $innerValue) {
			if (is_array($innerValue)) {
				$this->modifyPropertyMappingConfiguration($innerValue, $propertyMappingConfiguration->forProperty($innerKey));
			}
			$propertyMappingConfiguration->allowProperties($innerKey);
		}
	}
}
namespace TYPO3\Flow\Mvc\Controller;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * This is a Service which can generate a request hash and check whether the currently given arguments
 * fit to the request hash.
 * 
 * It is used when forms are generated and submitted:
 * After a form has been generated, the method "generateRequestHash" is called with the names of all form fields.
 * It cleans up the array of form fields and creates another representation of it, which is then serialized and hashed.
 * 
 * Both serialized form field list and the added hash form the request hash, which will be sent over the wire (as an argument __hmac).
 * 
 * On the validation side, the validation happens in two steps:
 * 1) Check if the request hash is consistent (the hash value fits to the serialized string)
 * 2) Check that _all_ GET/POST parameters submitted occur inside the form field list of the request hash.
 * 
 * Note: It is crucially important that a private key is computed into the hash value! This is done inside the HashService.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class MvcPropertyMappingConfigurationService extends MvcPropertyMappingConfigurationService_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', $this);
		if ('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', $propertyName, 'transient')) continue;
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