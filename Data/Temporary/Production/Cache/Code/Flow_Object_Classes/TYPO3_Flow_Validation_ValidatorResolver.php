<?php
namespace TYPO3\Flow\Validation;

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
use TYPO3\Flow\Object\Configuration\Configuration;
use TYPO3\Flow\Validation\Validator\ValidatorInterface;
use TYPO3\Flow\Validation\Validator\GenericObjectValidator;
use TYPO3\Flow\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ValidatorResolver_Original {

	/**
	 * Match validator names and options
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATORS = '/
			(?:^|,\s*)
			(?P<validatorName>[a-z0-9\\\\]+)
			\s*
			(?:\(
				(?P<validatorOptions>(?:\s*[a-z0-9]+\s*=\s*(?:
					"(?:\\\\"|[^"])*"
					|\'(?:\\\\\'|[^\'])*\'
					|(?:\s|[^,"\']*)
				)(?:\s|,)*)*)
			\))?
		/ixS';

	/**
	 * Match validator options (to parse actual options)
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATOROPTIONS = '/
			\s*
			(?P<optionName>[a-z0-9]+)
			\s*=\s*
			(?P<optionValue>
				"(?:\\\\"|[^"])*"
				|\'(?:\\\\\'|[^\'])*\'
				|(?:\s|[^,"\']*)
			)
		/ixS';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the TYPO3\Flow\Validation\Validator\ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return \TYPO3\Flow\Validation\Validator\ValidatorInterface
	 * @throws \TYPO3\Flow\Validation\Exception\NoSuchValidatorException
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationConfigurationException
	 * @api
	 */
	public function createValidator($validatorType, array $validatorOptions = array()) {
		$validatorObjectName = $this->resolveValidatorObjectName($validatorType);
		if ($validatorObjectName === FALSE) {
			return NULL;
		}

		switch ($this->objectManager->getScope($validatorObjectName)) {
			case Configuration::SCOPE_PROTOTYPE:
				$validator = new $validatorObjectName($validatorOptions);
				break;
			case Configuration::SCOPE_SINGLETON:
				if (count($validatorOptions) > 0) {
					throw new Exception\InvalidValidationConfigurationException('The validator "' . $validatorObjectName . '" is of scope singleton, but configured to be used with options. A validator with options must be of scope prototype.', 1358958575);
				}
				$validator = $this->objectManager->get($validatorObjectName);
				break;
			default:
				throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" is not of scope singleton or prototype!', 1300694835);
		}

		if (!($validator instanceof ValidatorInterface)) {
			throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" does not implement TYPO3\Flow\Validation\Validator\ValidatorInterface!', 1300694875);
		}

		return $validator;
	}

	/**
	 * Resolves and returns the base validator conjunction for the given data type.
	 *
	 * If no validation is necessary, the returned validator is empty.
	 *
	 * @param string $targetClassName Fully qualified class name of the target class, ie. the class which should be validated
	 * @param array $validationGroups The validation groups to build the validator for
	 * @return \TYPO3\Flow\Validation\Validator\ConjunctionValidator The validator conjunction
	 * @api
	 */
	public function getBaseValidatorConjunction($targetClassName, array $validationGroups = array('Default')) {
		$targetClassName = trim($targetClassName, ' \\');
		$indexKey = $targetClassName . '##' . implode('##', $validationGroups);
		if (!array_key_exists($indexKey, $this->baseValidatorConjunctions)) {
			$this->buildBaseValidatorConjunction($indexKey, $targetClassName, $validationGroups);
		}
		return $this->baseValidatorConjunctions[$indexKey];
	}

	/**
	 * Detects and registers any validators for arguments:
	 * - by the data type specified in the param annotations
	 * - additional validators specified in the validate annotations of a method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @param array $methodParameters Optional pre-compiled array of method parameters
	 * @param array $methodValidateAnnotations Optional pre-compiled array of validate annotations (as array)
	 * @return array An Array of ValidatorConjunctions for each method parameters.
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationConfigurationException
	 * @throws \TYPO3\Flow\Validation\Exception\NoSuchValidatorException
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidTypeHintException
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName, array $methodParameters = NULL, array $methodValidateAnnotations = NULL) {
		$validatorConjunctions = array();

		if ($methodParameters === NULL) {
			$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		}
		if (count($methodParameters) === 0) {
			return $validatorConjunctions;
		}

		foreach ($methodParameters as $parameterName => $methodParameter) {
			$validatorConjunction = $this->createValidator('TYPO3\Flow\Validation\Validator\ConjunctionValidator');

			if (!array_key_exists('type' , $methodParameter)) {
				throw new Exception\InvalidTypeHintException('Missing type information, probably no @param annotation for parameter "$' . $parameterName . '" in ' . $className . '->' . $methodName . '()', 1281962564);
			}
			if (strpos($methodParameter['type'], '\\') === FALSE) {
				$typeValidator = $this->createValidator($methodParameter['type']);
			} elseif (strpos($methodParameter['type'], '\\Model\\') !== FALSE) {
				$possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $methodParameter['type']) . 'Validator';
				$typeValidator = $this->createValidator($possibleValidatorClassName);
			} else {
				$typeValidator = NULL;
			}

			if ($typeValidator !== NULL) {
				$validatorConjunction->addValidator($typeValidator);
			}
			$validatorConjunctions[$parameterName] = $validatorConjunction;
		}

		if ($methodValidateAnnotations === NULL) {
			$validateAnnotations = $this->reflectionService->getMethodAnnotations($className, $methodName, 'TYPO3\Flow\Annotations\Validate');
			$methodValidateAnnotations = array_map(function($validateAnnotation) {
				return array(
					'type' => $validateAnnotation->type,
					'options' => $validateAnnotation->options,
					'argumentName' => $validateAnnotation->argumentName,
				);
			}, $validateAnnotations);
		}

		foreach ($methodValidateAnnotations as $annotationParameters) {
			$newValidator = $this->createValidator($annotationParameters['type'], $annotationParameters['options']);
			if ($newValidator === NULL) {
				throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $annotationParameters['type'] . '".', 1239853109);
			}
			if (isset($validatorConjunctions[$annotationParameters['argumentName']])) {
				$validatorConjunctions[$annotationParameters['argumentName']]->addValidator($newValidator);
			} elseif (strpos($annotationParameters['argumentName'], '.') !== FALSE) {
				$objectPath = explode('.', $annotationParameters['argumentName']);
				$argumentName = array_shift($objectPath);
				$validatorConjunctions[$argumentName]->addValidator($this->buildSubObjectValidator($objectPath, $newValidator));
			} else {
				throw new Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $annotationParameters['argumentName'] . '", but this argument does not exist.', 1253172726);
			}
		}
		return $validatorConjunctions;
	}

	/**
	 * Resets the baseValidatorConjunctions
	 * It is usually not required to reset the ValidatorResolver during one request. This method is mainly useful for functional tests
	 *
	 * @return void
	 */
	public function reset() {
		$this->baseValidatorConjunctions = array();
	}

	/**
	 * Builds a chain of nested object validators by specification of the given
	 * object path.
	 *
	 * @param array $objectPath The object path
	 * @param \TYPO3\Flow\Validation\Validator\ValidatorInterface $propertyValidator The validator which should be added to the property specified by objectPath
	 * @return \TYPO3\Flow\Validation\Validator\GenericObjectValidator
	 */
	protected function buildSubObjectValidator(array $objectPath, \TYPO3\Flow\Validation\Validator\ValidatorInterface $propertyValidator) {
		$rootObjectValidator = new GenericObjectValidator(array());
		$parentObjectValidator = $rootObjectValidator;

		while (count($objectPath) > 1) {
			$subObjectValidator = new GenericObjectValidator(array());
			$subPropertyName = array_shift($objectPath);
			$parentObjectValidator->addPropertyValidator($subPropertyName, $subObjectValidator);
			$parentObjectValidator = $subObjectValidator;
		}

		$parentObjectValidator->addPropertyValidator(array_shift($objectPath), $propertyValidator);
		return $rootObjectValidator;
	}

	/**
	 * Builds a base validator conjunction for the given data type.
	 *
	 * The base validation rules are those which were declared directly in a class (typically
	 * a model) through some validate annotations on properties.
	 *
	 * If a property holds a class for which a base validator exists, that property will be
	 * checked as well, regardless of a validate annotation
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "Replace '\Model\' by '\Validator\' and append 'Validator'".
	 *
	 * Example: $targetClassName is TYPO3\Foo\Domain\Model\Quux, then the validator will be found if it has the
	 * name TYPO3\Foo\Domain\Validator\QuuxValidator
	 *
	 * @param string $indexKey The key to use as index in $this->baseValidatorConjunctions; calculated from target class name and validation groups
	 * @param string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
	 * @param array $validationGroups The validation groups to build the validator for
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\NoSuchValidatorException
	 * @throws \InvalidArgumentException
	 */
	protected function buildBaseValidatorConjunction($indexKey, $targetClassName, array $validationGroups) {
		$conjunctionValidator = new ConjunctionValidator();
		$this->baseValidatorConjunctions[$indexKey] = $conjunctionValidator;
		if (class_exists($targetClassName)) {
				// Model based validator
			$objectValidator = new GenericObjectValidator(array());
			foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

				if (!isset($classPropertyTagsValues['var'])) {
					throw new \InvalidArgumentException(sprintf('There is no @var annotation for property "%s" in class "%s".', $classPropertyName, $targetClassName), 1363778104);
				}
				try {
					$parsedType = \TYPO3\Flow\Utility\TypeHandling::parseType(trim(implode('' , $classPropertyTagsValues['var']), ' \\'));
				} catch (\TYPO3\Flow\Utility\Exception\InvalidTypeException $exception) {
					throw new \InvalidArgumentException(sprintf(' @var annotation of ' . $exception->getMessage(), 'class "' . $targetClassName . '", property "' . $classPropertyName . '"'), 1315564744, $exception);
				}
				$propertyTargetClassName = $parsedType['type'];
				if (\TYPO3\Flow\Utility\TypeHandling::isCollectionType($propertyTargetClassName) === TRUE) {
						$collectionValidator = $this->createValidator('TYPO3\Flow\Validation\Validator\CollectionValidator', array('elementType' => $parsedType['elementType'], 'validationGroups' => $validationGroups));
						$objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
				} elseif (class_exists($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
					$validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName, $validationGroups);
					if (count($validatorForProperty) > 0) {
						$objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
					}
				}

				$validateAnnotations = $this->reflectionService->getPropertyAnnotations($targetClassName, $classPropertyName, 'TYPO3\Flow\Annotations\Validate');
				foreach ($validateAnnotations as $validateAnnotation) {
					if (count(array_intersect($validateAnnotation->validationGroups, $validationGroups)) === 0) {
						// In this case, the validation groups for the property do not match current validation context
						continue;
					}
					$newValidator = $this->createValidator($validateAnnotation->type, $validateAnnotation->options);
					if ($newValidator === NULL) {
						throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1241098027);
					}
					$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
				}
			}
			if (count($objectValidator->getPropertyValidators()) > 0) $conjunctionValidator->addValidator($objectValidator);

				// Custom validator for the class
			$possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $targetClassName) . 'Validator';
			$customValidator = $this->createValidator($possibleValidatorClassName);
			if ($customValidator !== NULL) {
				$conjunctionValidator->addValidator($customValidator);
			}
		}
	}

	/**
	 * Returns the class name of an appropriate validator for the given type. If no
	 * validator is available FALSE is returned
	 *
	 * @param string $validatorType Either the fully qualified class name of the validator or the short name of a built-in validator
	 * @return string|boolean Class name of the validator or FALSE if not available
	 */
	protected function resolveValidatorObjectName($validatorType) {
		$validatorType = ltrim($validatorType, '\\');

		$validatorClassNames = static::getValidatorImplementationClassNames($this->objectManager);

		if ($this->objectManager->isRegistered($validatorType) && isset($validatorClassNames[$validatorType])) {
			return $validatorType;
		}

		if (strpos($validatorType, ':') !== FALSE) {
			list($packageName, $packageValidatorType) = explode(':', $validatorType);
			$possibleClassName = sprintf('%s\Validation\Validator\%sValidator', str_replace('.', '\\', $packageName), $this->getValidatorType($packageValidatorType));
		} else {
			$possibleClassName = sprintf('TYPO3\Flow\Validation\Validator\%sValidator', $this->getValidatorType($validatorType));
		}
		if ($this->objectManager->isRegistered($possibleClassName) && isset($validatorClassNames[$possibleClassName])) {
			return $possibleClassName;
		}

		return FALSE;
	}

	/**
	 * Returns all class names implementing the ValidatorInterface.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of class names implementing ValidatorInterface indexed by class name
	 * @Flow\CompileStatic
	 */
	static public function getValidatorImplementationClassNames($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		$classNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Validation\Validator\ValidatorInterface');
		return array_flip($classNames);
	}

	/**
	 * Used to map PHP types to validator types.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	protected function getValidatorType($type) {
		switch ($type) {
			case 'int':
				$type = 'Integer';
				break;
			case 'bool':
				$type = 'Boolean';
				break;
			case 'double':
				$type = 'Float';
				break;
			case 'numeric':
				$type = 'Number';
				break;
			case 'mixed':
				$type = 'Raw';
				break;
			default:
				$type = ucfirst($type);
		}
		return $type;
	}
}

namespace TYPO3\Flow\Validation;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class ValidatorResolver extends ValidatorResolver_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Validation\ValidatorResolver') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Validation\ValidatorResolver', $this);
		if ('TYPO3\Flow\Validation\ValidatorResolver' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Validation\ValidatorResolver') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Validation\ValidatorResolver', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Validation\ValidatorResolver');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Validation\ValidatorResolver', $propertyName, 'transient')) continue;
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
		$objectManager_reference = &$this->objectManager;
		$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Object\ObjectManagerInterface');
		if ($this->objectManager === NULL) {
			$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('0c3c44be7be16f2a287f1fb2d068dde4', $objectManager_reference);
			if ($this->objectManager === NULL) {
				$this->objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('0c3c44be7be16f2a287f1fb2d068dde4',  $objectManager_reference, 'TYPO3\Flow\Object\ObjectManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'); });
			}
		}
		$reflectionService_reference = &$this->reflectionService;
		$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\ReflectionService');
		if ($this->reflectionService === NULL) {
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('921ad637f16d2059757a908fceaf7076', $reflectionService_reference);
			if ($this->reflectionService === NULL) {
				$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('921ad637f16d2059757a908fceaf7076',  $reflectionService_reference, 'TYPO3\Flow\Reflection\ReflectionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'); });
			}
		}
	}

	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of class names implementing ValidatorInterface indexed by class name
	 * @\TYPO3\Flow\Annotations\CompileStatic
	 */
	static public function getValidatorImplementationClassNames($objectManager) {

return array (
  'TYPO3\\Flow\\Validation\\Validator\\AlphanumericValidator' => 0,
  'TYPO3\\Flow\\Validation\\Validator\\GenericObjectValidator' => 1,
  'TYPO3\\Flow\\Validation\\Validator\\CollectionValidator' => 2,
  'TYPO3\\Flow\\Validation\\Validator\\ConjunctionValidator' => 3,
  'TYPO3\\Flow\\Validation\\Validator\\CountValidator' => 4,
  'TYPO3\\Flow\\Validation\\Validator\\DateTimeRangeValidator' => 5,
  'TYPO3\\Flow\\Validation\\Validator\\DateTimeValidator' => 6,
  'TYPO3\\Flow\\Validation\\Validator\\DisjunctionValidator' => 7,
  'TYPO3\\Flow\\Validation\\Validator\\EmailAddressValidator' => 8,
  'TYPO3\\Flow\\Validation\\Validator\\FloatValidator' => 9,
  'TYPO3\\Flow\\Validation\\Validator\\IntegerValidator' => 10,
  'TYPO3\\Flow\\Validation\\Validator\\LabelValidator' => 11,
  'TYPO3\\Flow\\Validation\\Validator\\LocaleIdentifierValidator' => 12,
  'TYPO3\\Flow\\Validation\\Validator\\NotEmptyValidator' => 13,
  'TYPO3\\Flow\\Validation\\Validator\\NumberRangeValidator' => 14,
  'TYPO3\\Flow\\Validation\\Validator\\NumberValidator' => 15,
  'TYPO3\\Flow\\Validation\\Validator\\RawValidator' => 16,
  'TYPO3\\Flow\\Validation\\Validator\\RegularExpressionValidator' => 17,
  'TYPO3\\Flow\\Validation\\Validator\\StringLengthValidator' => 18,
  'TYPO3\\Flow\\Validation\\Validator\\StringValidator' => 19,
  'TYPO3\\Flow\\Validation\\Validator\\TextValidator' => 20,
  'TYPO3\\Flow\\Validation\\Validator\\UniqueEntityValidator' => 21,
  'TYPO3\\Flow\\Validation\\Validator\\UuidValidator' => 22,
  'TYPO3\\Party\\Domain\\Validator\\ElectronicAddressValidator' => 23,
  'TYPO3\\Party\\Domain\\Validator\\PersonNameValidator' => 24,
);
	}
}
#