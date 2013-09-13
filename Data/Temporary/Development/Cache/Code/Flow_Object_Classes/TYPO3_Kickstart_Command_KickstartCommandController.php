<?php
namespace TYPO3\Kickstart\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Kickstart".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for the Kickstart generator
 *
 */
class KickstartCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Kickstart\Service\GeneratorService
	 * @Flow\Inject
	 */
	protected $generatorService;

	/**
	 * Kickstart a new package
	 *
	 * Creates a new package and creates a standard Action Controller and a sample
	 * template for its Index Action.
	 *
	 * For creating a new package without sample code use the package:create command.
	 *
	 * @param string $packageKey The package key, for example "MyCompany.MyPackageName"
	 * @return string
	 * @see typo3.flow:package:create
	 */
	public function packageCommand($packageKey) {
		$this->validatePackageKey($packageKey);

		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" already exists.', array($packageKey));
			$this->quit(2);
		}
		$this->packageManager->createPackage($packageKey);
		$this->actionControllerCommand($packageKey, 'Standard');
	}

	/**
	 * Kickstart a new action controller
	 *
	 * Generates an Action Controller with the given name in the specified package.
	 * In its default mode it will create just the controller containing a sample
	 * indexAction.
	 *
	 * By specifying the --generate-actions flag, this command will also create a
	 * set of actions. If no model or repository exists which matches the
	 * controller name (for example "CoffeeRepository" for "CoffeeController"),
	 * an error will be shown.
	 *
	 * Likewise the command exits with an error if the specified package does not
	 * exist. By using the --generate-related flag, a missing package, model or
	 * repository can be created alongside, avoiding such an error.
	 *
	 * By specifying the --generate-templates flag, this command will also create
	 * matching Fluid templates for the actions created. This option can only be
	 * used in combination with --generate-actions.
	 *
	 * The default behavior is to not overwrite any existing code. This can be
	 * overridden by specifying the --force flag.
	 *
	 * @param string $packageKey The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $generateActions Also generate index, show, new, create, edit, update and delete actions.
	 * @param boolean $generateTemplates Also generate the templates for each action.
	 * @param boolean $generateRelated Also create the mentioned package, related model and repository if neccessary.
	 * @param boolean $force Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.
	 * @return string
	 * @see typo3.kickstart:kickstart:commandcontroller
	 */
	public function actionControllerCommand($packageKey, $controllerName, $generateActions = FALSE, $generateTemplates = TRUE, $generateRelated = FALSE, $force = FALSE) {
		$subpackageName = '';
		if (strpos($packageKey, '/') !== FALSE) {
			list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
		}
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			if ($generateRelated === FALSE) {
				$this->outputLine('Package "%s" is not available.', array($packageKey));
				$this->outputLine('Hint: Use --generate-related for creating it!');
				$this->quit(2);
			}
			$this->packageManager->createPackage($packageKey);
		}
		$generatedFiles = array();
		$generatedModels = FALSE;

		$controllerNames = \TYPO3\Flow\Utility\Arrays::trimExplode(',', $controllerName);
		if ($generateActions === TRUE) {
			foreach ($controllerNames as $currentControllerName) {
				$modelClassName = str_replace('.', '\\', $packageKey) . '\Domain\Model\\' . $currentControllerName;
				if (!class_exists($modelClassName)) {
					if ($generateRelated === TRUE) {
						$generatedFiles += $this->generatorService->generateModel($packageKey, $currentControllerName, array('name' => array('type' => 'string')));
						$generatedModels = TRUE;
					} else {
						$this->outputLine('The model %s does not exist, but is necessary for creating the respective actions.', array($modelClassName));
						$this->outputLine('Hint: Use --generate-related for creating it!');
						$this->quit(3);
					}
				}

				$repositoryClassName = str_replace('.', '\\', $packageKey) . '\Domain\Repository\\' . $currentControllerName . 'Repository';
				if (!class_exists($repositoryClassName)) {
					if ($generateRelated === TRUE) {
						$generatedFiles += $this->generatorService->generateRepository($packageKey, $currentControllerName);
					} else {
						$this->outputLine('The repository %s does not exist, but is necessary for creating the respective actions.', array($repositoryClassName));
						$this->outputLine('Hint: Use --generate-related for creating it!');
						$this->quit(4);
					}
				}
			}
		}

		foreach ($controllerNames as $currentControllerName) {
			if ($generateActions === TRUE) {
				$generatedFiles += $this->generatorService->generateCrudController($packageKey, $subpackageName, $currentControllerName, $force);
			} else {
				$generatedFiles += $this->generatorService->generateActionController($packageKey, $subpackageName, $currentControllerName, $force);
			}
			if ($generateTemplates === TRUE) {
				$generatedFiles += $this->generatorService->generateLayout($packageKey, 'Default', $force);
				if ($generateActions === TRUE) {
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'Index', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'New', 'New', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Edit', 'Edit', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Show', 'Show', $force);
				} else {
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'SampleIndex', $force);
				}
			}
		}

		$this->outputLine(implode(PHP_EOL, $generatedFiles));

		if ($generatedModels === TRUE) {
			$this->outputLine('As new models were generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
		}
	}

	/**
	 * Kickstart a new command controller
	 *
	 * Creates a new command controller with the given name in the specified
	 * package. The generated controller class already contains an example command.
	 *
	 * @param string $packageKey The package key of the package for the new controller
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $force Overwrite any existing controller.
	 * @return string
	 * @see typo3.kickstart:kickstart:actioncontroller
	 */
	public function commandControllerCommand($packageKey, $controllerName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			$this->quit(2);
		}
		$generatedFiles = array();
		$controllerNames = \TYPO3\Flow\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateCommandController($packageKey, $currentControllerName, $force);
		}
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
	}

	/**
	 * Kickstart a new domain model
	 *
	 * This command generates a new domain model class. The fields are specified as
	 * a variable list of arguments with field name and type separated by a colon
	 * (for example "title:string" "size:int" "type:MyType").
	 *
	 * @param string $packageKey The package key of the package for the domain model
	 * @param string $modelName The name of the new domain model class
	 * @param boolean $force Overwrite any existing model.
	 * @return string
	 * @see typo3.kickstart:kickstart:repository
	 */
	public function modelCommand($packageKey, $modelName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			$this->quit(2);
		}

		$fieldsArguments = $this->request->getExceedingArguments();
		$fieldDefinitions = array();
		foreach ($fieldsArguments as $fieldArgument) {
			list($fieldName, $fieldType) = explode(':', $fieldArgument, 2);

			$fieldDefinitions[$fieldName] = array('type' => $fieldType);
			if (strpos($fieldType, 'array') !== FALSE) {
				$fieldDefinitions[$fieldName]['typeHint'] = 'array';
			} elseif (strpos($fieldType, '\\') !== FALSE) {
				if (strpos($fieldType, '<') !== FALSE) {
					$fieldDefinitions[$fieldName]['typeHint'] = substr($fieldType, 0, strpos($fieldType, '<'));
				} else {
					$fieldDefinitions[$fieldName]['typeHint'] = $fieldType;
				}
			}
		};

		$generatedFiles = $this->generatorService->generateModel($packageKey, $modelName, $fieldDefinitions, $force);
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
		$this->outputLine('As a new model was generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
	}

	/**
	 * Kickstart a new domain repository
	 *
	 * This command generates a new domain repository class for the given model name.
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the domain model class
	 * @param boolean $force Overwrite any existing repository.
	 * @return string
	 * @see typo3.kickstart:kickstart:model
	 */
	public function repositoryCommand($packageKey, $modelName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			$this->quit(2);
		}

		$generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName, $force);
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
	}

	/**
	 * Checks the syntax of the given $packageKey and quits with an error message if it's not valid
	 *
	 * @param string $packageKey
	 * @return void
	 */
	protected function validatePackageKey($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->outputLine('Package key "%s" is not valid. Only UpperCamelCase with alphanumeric characters in the format <VendorName>.<PackageKey>, please!', array($packageKey));
			$this->quit(1);
		}
	}

}
namespace TYPO3\Kickstart\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for the Kickstart generator
 */
class KickstartCommandController extends KickstartCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Kickstart\Command\KickstartCommandController' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Kickstart\Command\KickstartCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Kickstart\Command\KickstartCommandController', $propertyName, 'transient')) continue;
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
		$packageManager_reference = &$this->packageManager;
		$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Package\PackageManagerInterface');
		if ($this->packageManager === NULL) {
			$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('aad0cdb65adb124cf4b4d16c5b42256c', $packageManager_reference);
			if ($this->packageManager === NULL) {
				$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('aad0cdb65adb124cf4b4d16c5b42256c',  $packageManager_reference, 'TYPO3\Flow\Package\PackageManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'); });
			}
		}
		$this->generatorService = new \TYPO3\Kickstart\Service\GeneratorService();
	}
}
#