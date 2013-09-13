<?php
namespace TYPO3\Flow\Command;

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
 * Configuration command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class ConfigurationCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaValidator
	 */
	protected $schemaValidator;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaGenerator
	 */
	protected $schemaGenerator;

	/**
	 * Show the active configuration settings
	 *
	 * The command shows the configuration of the current context as it is used by Flow itself.
	 * You can specify the configuration type and path if you want to show parts of the configuration.
	 *
	 * ./flow configuration:show --type Settings --path TYPO3.Flow.persistence
	 *
	 * @param string $type Configuration type to show
	 * @param string $path path to subconfiguration separated by "." like "TYPO3.Flow"
	 * @return void
	 */
	public function showCommand($type = NULL, $path = NULL) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		if (in_array($type, $availableConfigurationTypes)) {
			$configuration = $this->configurationManager->getConfiguration($type);
			if ($path !== NULL) {
				$configuration = \TYPO3\Flow\Utility\Arrays::getValueByPath($configuration, $path);
			}
			$typeAndPath = $type . ($path ? ': ' . $path : '');
			if ($configuration === NULL) {
				$this->outputLine('<b>Configuration "%s" was empty!</b>', array($typeAndPath));
			} else {
				$yaml = \Symfony\Component\Yaml\Yaml::dump($configuration, 99);
				$this->outputLine('<b>Configuration "%s":</b>', array($typeAndPath));
				$this->outputLine();
				$this->outputLine($yaml . chr(10));
			}
		} else {
			if ($type !== NULL) {
				$this->outputLine('<b>Configuration type "%s" was not found!</b>', array($type));
			}
			$this->outputLine('<b>Available configuration types:</b>');
			foreach ($availableConfigurationTypes as $availableConfigurationType) {
				$this->outputLine('  ' . $availableConfigurationType);
			}
			$this->outputLine();
			$this->outputLine('Hint: <b>%s configuration:show --type <configurationType></b>', array($this->getFlowInvocationString()));
			$this->outputLine('      shows the configuration of the specified type.');
		}
	}

	/**
	 * Validate the given configuration
	 *
	 * ./flow configuration:validate --type Settings --path TYPO3.Flow.persistence
	 *
	 * The schemas are searched in the path "Resources/Private/Schema" of all
	 * active Packages. The schema-filenames must match the pattern
	 * __type__.__path__.schema.yaml. The type and/or the path can also be
	 * expressed as subdirectories of Resources/Private/Schema. So
	 * Settings/TYPO3/Flow.persistence.schema.yaml will match the same pathes
	 * like Settings.TYPO3.Flow.persistence.schema.yaml or
	 * Settings/TYPO3.Flow/persistence.schema.yaml
	 *
	 * @param string $type Configuration type to validate
	 * @param string $path path to the subconfiguration separated by "." like "TYPO3.Flow"
	 * @return void
	 */
	public function validateCommand($type = NULL, $path = NULL) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();

		if (in_array($type, $availableConfigurationTypes) === FALSE) {
			if ($type !== NULL) {
				$this->outputLine('<b>Configuration type "%s" was not found!</b>', array($type));
				$this->outputLine();
			}
			$this->outputLine('<b>Available configuration types:</b>');
			foreach ($availableConfigurationTypes as $availableConfigurationType) {
				$this->outputLine('  ' . $availableConfigurationType);
			}
			$this->outputLine();
			$this->outputLine('Hint: <b>%s configuration:validate --type <configurationType></b>', array($this->getFlowInvocationString()));
			$this->outputLine('      validates the configuration of the specified type.');
			return;
		}

		$configuration = $this->configurationManager->getConfiguration($type);

		$this->outputLine('<b>Validating configuration for type: "' . $type . '"' . (($path !== NULL) ? ' and path: "' . $path . '"': '') . '</b>');

			// find schema files for the given type and path
		$schemaFileInfos = array();
		$activePackages = $this->packageManager->getActivePackages();
		foreach ($activePackages as $package) {
			$packageKey = $package->getPackageKey();
			$packageSchemaPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($package->getResourcesPath(), 'Private/Schema'));
			if (is_dir($packageSchemaPath)) {
				$packageSchemaFiles = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($packageSchemaPath, '.schema.yaml');
				foreach ($packageSchemaFiles as $schemaFile) {
					$schemaName = substr($schemaFile, strlen($packageSchemaPath) + 1, -strlen('.schema.yaml'));
					$schemaNameParts = explode('.', str_replace('/', '.' ,$schemaName), 2);

					$schemaType = $schemaNameParts[0];
					$schemaPath = isset($schemaNameParts[1]) ? $schemaNameParts[1] : NULL;

					if ($schemaType === $type && ($path === NULL || strpos($schemaPath, $path) === 0)){
						$schemaFileInfos[] = array(
							'file' => $schemaFile,
							'name' => $schemaName,
							'path' => $schemaPath,
							'packageKey' => $packageKey
						);
					}
				}
			}
		}

		$this->outputLine();
		if (count($schemaFileInfos) > 0) {
			$this->outputLine('%s schema files were found:', array(count($schemaFileInfos)));
			$result = new \TYPO3\Flow\Error\Result();
			foreach ($schemaFileInfos as $schemaFileInfo) {

				if ($schemaFileInfo['path'] !== NULL) {
					$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($configuration, $schemaFileInfo['path']);
				} else {
					$data = $configuration;
				}

				if (empty($data)){
					$result->forProperty($schemaFileInfo['path'])->addError(new \TYPO3\Flow\Error\Error('configuration in path ' . $schemaFileInfo['path'] . ' is empty'));
					$this->outputLine(' - package: "' . $schemaFileInfo['packageKey'] . '" schema: "' . $schemaFileInfo['name'] . '" -> <b>configuration is empty</b>');
				} else {
					$parsedSchema = \Symfony\Component\Yaml\Yaml::parse($schemaFileInfo['file']);
					$schemaResult = $this->schemaValidator->validate($data, $parsedSchema);

					if ($schemaResult->hasErrors()) {
						$this->outputLine(' - package:"' . $schemaFileInfo['packageKey'] . '" schema:"' . $schemaFileInfo['name'] . '" -> <b>' .  count($schemaResult->getFlattenedErrors()) . ' errors</b>');
					} else {
						$this->outputLine(' - package:"' . $schemaFileInfo['packageKey'] . '" schema:"' . $schemaFileInfo['name'] . '" -> <b>is valid</b>');
					}

					if ($schemaFileInfo['path'] !== NULL) {
						$result->forProperty($schemaFileInfo['path'])->merge($schemaResult);
					} else {
						$result->merge($schemaResult);
					}
				}
			}
		} else {
			$this->outputLine('No matching schema-files were found!');
			return;
		}

		$this->outputLine();
		if ($result->hasErrors()) {
			$errors = $result->getFlattenedErrors();
			$this->outputLine('<b>%s errors were found:</b>', array(count($errors)));
			foreach ($errors as $path => $pathErrors){
				foreach ($pathErrors as $error){
					$this->outputLine(' - %s -> %s', array($path, $error->render()));
				}
			}
		} else {
			$this->outputLine('<b>The configuration is valid!</b>');
		}
	}

	/**
	 * Generate a schema for the given configuration or YAML file.
	 *
	 * ./flow configuration:generateschema --type Settings --path TYPO3.Flow.persistence
	 *
	 * The schema will be output to standard output.
	 *
	 * @param string $type Configuration type to create a schema for
	 * @param string $path path to the subconfiguration separated by "." like "TYPO3.Flow"
	 * @param string $yaml YAML file to create a schema for
	 * @return void
	 */
	public function generateSchemaCommand($type = NULL, $path = NULL, $yaml = NULL) {
		$data = NULL;
		if ($yaml !== NULL && is_file($yaml) && is_readable($yaml)) {
			$data = \Symfony\Component\Yaml\Yaml::parse($yaml);
		} elseif ($type !== NULL) {
			$data = $this->configurationManager->getConfiguration($type);
			if ($path !== NULL){
				$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($data, $path);
			}
		}

		if (empty($data)){
			$this->outputLine('Data was not found or is empty');
			return;
		}

		$yaml = \Symfony\Component\Yaml\Yaml::dump($this->schemaGenerator->generate($data), 99);
		$this->output($yaml . chr(10));
	}

}
namespace TYPO3\Flow\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Configuration command controller for the TYPO3.Flow package
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class ConfigurationCommandController extends ConfigurationCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Command\ConfigurationCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\ConfigurationCommandController', $this);
		parent::__construct();
		if ('TYPO3\Flow\Command\ConfigurationCommandController' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Command\ConfigurationCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\ConfigurationCommandController', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Command\ConfigurationCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Command\ConfigurationCommandController', $propertyName, 'transient')) continue;
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
		$configurationManager_reference = &$this->configurationManager;
		$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		if ($this->configurationManager === NULL) {
			$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('13edcae8fd67699bb78dadc8c1eac29c', $configurationManager_reference);
			if ($this->configurationManager === NULL) {
				$this->configurationManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('13edcae8fd67699bb78dadc8c1eac29c',  $configurationManager_reference, 'TYPO3\Flow\Configuration\ConfigurationManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager'); });
			}
		}
		$schemaValidator_reference = &$this->schemaValidator;
		$this->schemaValidator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Utility\SchemaValidator');
		if ($this->schemaValidator === NULL) {
			$this->schemaValidator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('1aee6e37460cf41f65ff674c80107cff', $schemaValidator_reference);
			if ($this->schemaValidator === NULL) {
				$this->schemaValidator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('1aee6e37460cf41f65ff674c80107cff',  $schemaValidator_reference, 'TYPO3\Flow\Utility\SchemaValidator', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\SchemaValidator'); });
			}
		}
		$schemaGenerator_reference = &$this->schemaGenerator;
		$this->schemaGenerator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Utility\SchemaGenerator');
		if ($this->schemaGenerator === NULL) {
			$this->schemaGenerator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('a96b1c047313d06a902dbb0d1d6f06d3', $schemaGenerator_reference);
			if ($this->schemaGenerator === NULL) {
				$this->schemaGenerator = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('a96b1c047313d06a902dbb0d1d6f06d3',  $schemaGenerator_reference, 'TYPO3\Flow\Utility\SchemaGenerator', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\SchemaGenerator'); });
			}
		}
	}
}
#