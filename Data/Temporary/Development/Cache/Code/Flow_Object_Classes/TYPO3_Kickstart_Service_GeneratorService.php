<?php
namespace TYPO3\Kickstart\Service;

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
 * Service for the Kickstart generator
 *
 */
class GeneratorService_Original {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Fluid\Core\Parser\TemplateParser
	 * @Flow\Inject
	 */
	protected $templateParser;

	/**
	 * @var \TYPO3\Kickstart\Utility\Inflector
	 * @Flow\Inject
	 */
	protected $inflector;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $generatedFiles = array();

	/**
	 * Generate a controller with the given name for the given package
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $subpackage An optional subpackage name
	 * @param string $controllerName The name of the new controller
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateActionController($packageKey, $subpackage, $controllerName, $overwrite = FALSE) {
		$controllerName = ucfirst($controllerName);
		$controllerClassName = $controllerName . 'Controller';

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/ActionControllerTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
		$contextVariables['subpackage'] = $subpackage;
		$contextVariables['isInSubpackage'] = ($subpackage != '');
		$contextVariables['controllerClassName'] = $controllerClassName;
		$contextVariables['controllerName'] = $controllerName;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
		$controllerFilename = $controllerClassName . '.php';
		$controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . $subpackagePath . 'Controller/';
		$targetPathAndFilename = $controllerPath . $controllerFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate an Action Controller with pre-made CRUD methods
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $subpackage An optional subpackage name
	 * @param string $controllerName The name of the new controller
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateCrudController($packageKey, $subpackage, $controllerName, $overwrite = FALSE) {
		$controllerName = ucfirst($controllerName);
		$controllerClassName = $controllerName . 'Controller';

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/CrudControllerTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
		$contextVariables['subpackage'] = $subpackage;
		$contextVariables['isInSubpackage'] = ($subpackage != '');
		$contextVariables['controllerClassName'] = $controllerClassName;
		$contextVariables['controllerName'] = $controllerName;
		$contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
		$contextVariables['repositoryClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
		$contextVariables['modelFullClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
		$contextVariables['modelClassName'] = ucfirst($contextVariables['modelName']);

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
		$controllerFilename = $controllerClassName . '.php';
		$controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . $subpackagePath . 'Controller/';
		$targetPathAndFilename = $controllerPath . $controllerFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a command controller with the given name for the given package
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $controllerName The name of the new controller
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateCommandController($packageKey, $controllerName, $overwrite = FALSE) {
		$controllerName = ucfirst($controllerName) . 'Command';
		$controllerClassName = $controllerName . 'Controller';

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/CommandControllerTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
		$contextVariables['controllerClassName'] = $controllerClassName;
		$contextVariables['controllerName'] = $controllerName;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$controllerFilename = $controllerClassName . '.php';
		$controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Command/';
		$targetPathAndFilename = $controllerPath . $controllerFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a view with the given name for the given package and controller
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $subpackage An optional subpackage name
	 * @param string $controllerName The name of the new controller
	 * @param string $viewName The name of the view
	 * @param string $templateName The name of the view
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateView($packageKey, $subpackage, $controllerName, $viewName, $templateName, $overwrite = FALSE) {
		$viewName = ucfirst($viewName);

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/View/' . $templateName . 'Template.html';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['subpackage'] = $subpackage;
		$contextVariables['isInSubpackage'] = ($subpackage != '');
		$contextVariables['controllerName'] = $controllerName;
		$contextVariables['viewName'] = $viewName;
		$contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
		$contextVariables['repositoryClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
		$contextVariables['modelFullClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
		$contextVariables['modelClassName'] = ucfirst($contextVariables['modelName']);

		$modelClassSchema = $this->reflectionService->getClassSchema($contextVariables['modelFullClassName']);
		if ($modelClassSchema !== NULL) {
			$contextVariables['properties'] = $modelClassSchema->getProperties();
			if (isset($contextVariables['properties']['Persistence_Object_Identifier'])) {
				unset($contextVariables['properties']['Persistence_Object_Identifier']);
			}
		}

		if (!isset($contextVariables['properties']) || $contextVariables['properties'] === array()) {
			$contextVariables['properties'] = array('name' => array('type' => 'string'));
		}

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
		$viewFilename = $viewName . '.html';
		$viewPath = 'resource://' . $packageKey . '/Private/Templates/' . $subpackagePath . $controllerName . '/';
		$targetPathAndFilename = $viewPath . $viewFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a default layout
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $layoutName The name of the layout
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateLayout($packageKey, $layoutName, $overwrite = FALSE) {
		$layoutName = ucfirst($layoutName);

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/View/' . $layoutName . 'Layout.html';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$layoutFilename = $layoutName . '.html';
		$viewPath = 'resource://' . $packageKey . '/Private/Layouts/';
		$targetPathAndFilename = $viewPath . $layoutFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a model for the package with the given model name and fields
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $modelName The name of the new model
	 * @param array $fieldDefinitions The field definitions
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateModel($packageKey, $modelName, array $fieldDefinitions, $overwrite = FALSE) {
		$modelName = ucfirst($modelName);
		$namespace = str_replace('.', '\\', $packageKey) .  '\\Domain\\Model';
		$fieldDefinitions = $this->normalizeFieldDefinitions($fieldDefinitions, $namespace);

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Model/EntityTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['modelName'] = $modelName;
		$contextVariables['fieldDefinitions'] = $fieldDefinitions;
		$contextVariables['namespace'] = $namespace;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$modelFilename = $modelName . '.php';
		$modelPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Domain/Model/';
		$targetPathAndFilename = $modelPath . $modelFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		$this->generateTestsForModel($packageKey, $modelName, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a dummy testcase for a model for the package with the given model name
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $modelName The name of the new model fpr which to generate the test
	 * @param boolean $overwrite Overwrite any existing files?
	 * @return array An array of generated filenames
	 */
	public function generateTestsForModel($packageKey, $modelName, $overwrite = FALSE) {
		$testName = ucfirst($modelName) . 'Test';
		$namespace = str_replace('.', '\\', $packageKey) .  '\\Tests\\Unit\\Domain\\Model';

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Tests/Unit/Model/EntityTestTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['testName'] = $testName;
		$contextVariables['modelName'] = $modelName;
		$contextVariables['namespace'] = $namespace;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$testFilename = $testName . '.php';
		$testPath = $this->packageManager->getPackage($packageKey)->getPackagePath() . \TYPO3\Flow\Package\PackageInterface::DIRECTORY_TESTS_UNIT . 'Domain/Model/';
		$targetPathAndFilename = $testPath . $testFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Generate a repository for a model given a model name and package key
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the model
	 * @return array An array of generated filenames
	 * @param boolean $overwrite Overwrite any existing files?
	 */
	public function generateRepository($packageKey, $modelName, $overwrite = FALSE) {
		$modelName = ucfirst($modelName);
		$repositoryClassName = $modelName . 'Repository';
		$namespace = str_replace('.', '\\', $packageKey) .  '\\Domain\\Repository';

		$templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Repository/RepositoryTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['modelName'] = $modelName;
		$contextVariables['repositoryClassName'] = $repositoryClassName;
		$contextVariables['namespace'] = $namespace;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$repositoryFilename = $repositoryClassName . '.php';
		$repositoryPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Domain/Repository/';
		$targetPathAndFilename = $repositoryPath . $repositoryFilename;

		$this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

		return $this->generatedFiles;
	}

	/**
	 * Normalize types and prefix types with namespaces
	 *
	 * @param array $fieldDefinitions The field definitions
	 * @param string $namespace The namespace
	 * @return array The normalized and type converted field definitions
	 */
	protected function normalizeFieldDefinitions(array $fieldDefinitions, $namespace = '') {
		foreach ($fieldDefinitions as &$fieldDefinition) {
			if ($fieldDefinition['type'] == 'bool') {
				$fieldDefinition['type'] = 'boolean';
			} elseif ($fieldDefinition['type'] == 'int') {
				$fieldDefinition['type'] = 'integer';
			} else if (preg_match('/^[A-Z]/', $fieldDefinition['type'])) {
				if (class_exists($fieldDefinition['type'])) {
					$fieldDefinition['type'] = '\\' . $fieldDefinition['type'];
				} else {
					$fieldDefinition['type'] = '\\' . $namespace . '\\' . $fieldDefinition['type'];
				}
			}
		}
		return $fieldDefinitions;
	}

	/**
	 * Generate a file with the given content and add it to the
	 * generated files
	 *
	 * @param string $targetPathAndFilename
	 * @param string $fileContent
	 * @param boolean $force
	 * @return void
	 */
	protected function generateFile($targetPathAndFilename, $fileContent, $force = FALSE) {
		if (!is_dir(dirname($targetPathAndFilename))) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		if (substr($targetPathAndFilename, 0, 11) === 'resource://') {
			list($packageKey, $resourcePath) = explode('/', substr($targetPathAndFilename, 11), 2);
			$relativeTargetPathAndFilename = $packageKey . '/Resources/' . $resourcePath;
		} elseif (strpos($targetPathAndFilename, 'Tests') !== FALSE) {
			$relativeTargetPathAndFilename = substr($targetPathAndFilename, strrpos(substr($targetPathAndFilename, 0, strpos($targetPathAndFilename, 'Tests/') - 1), '/') + 1);
		} else {
			$relativeTargetPathAndFilename = substr($targetPathAndFilename, strrpos(substr($targetPathAndFilename, 0, strpos($targetPathAndFilename, 'Classes/') - 1), '/') + 1);
		}

		if (!file_exists($targetPathAndFilename) || $force === TRUE) {
			file_put_contents($targetPathAndFilename, $fileContent);
			$this->generatedFiles[] = 'Created .../' . $relativeTargetPathAndFilename;
		} else {
			$this->generatedFiles[] = 'Omitted .../' . $relativeTargetPathAndFilename;
		}
	}

	/**
	 * Render the given template file with the given variables
	 *
	 * @param string $templatePathAndFilename
	 * @param array $contextVariables
	 * @return string
	 * @throws \TYPO3\Fluid\Core\Exception
	 */
	protected function renderTemplate($templatePathAndFilename, array $contextVariables) {
		$templateSource = \TYPO3\Flow\Utility\Files::getFileContents($templatePathAndFilename, FILE_TEXT);
		if ($templateSource === FALSE) {
			throw new \TYPO3\Fluid\Core\Exception('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
		}
		$parsedTemplate = $this->templateParser->parse($templateSource);

		$renderingContext = $this->buildRenderingContext($contextVariables);

		return $parsedTemplate->render($renderingContext);
	}

	/**
	 * Build the rendering context
	 *
	 * @param array $contextVariables
	 * @return \TYPO3\Fluid\Core\Rendering\RenderingContext
	 */
	protected function buildRenderingContext(array $contextVariables) {
		$renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();

		$renderingContext->injectTemplateVariableContainer(new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer($contextVariables));
		$renderingContext->injectViewHelperVariableContainer(new \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer());

		return $renderingContext;
	}
}
namespace TYPO3\Kickstart\Service;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Service for the Kickstart generator
 */
class GeneratorService extends GeneratorService_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Kickstart\Service\GeneratorService' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Kickstart\Service\GeneratorService');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Kickstart\Service\GeneratorService', $propertyName, 'transient')) continue;
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
		$packageManager_reference = &$this->packageManager;
		$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Package\PackageManagerInterface');
		if ($this->packageManager === NULL) {
			$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('aad0cdb65adb124cf4b4d16c5b42256c', $packageManager_reference);
			if ($this->packageManager === NULL) {
				$this->packageManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('aad0cdb65adb124cf4b4d16c5b42256c',  $packageManager_reference, 'TYPO3\Flow\Package\PackageManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'); });
			}
		}
		$this->templateParser = new \TYPO3\Fluid\Core\Parser\TemplateParser();
		$this->inflector = new \TYPO3\Kickstart\Utility\Inflector();
		$reflectionService_reference = &$this->reflectionService;
		$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\ReflectionService');
		if ($this->reflectionService === NULL) {
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('921ad637f16d2059757a908fceaf7076', $reflectionService_reference);
			if ($this->reflectionService === NULL) {
				$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('921ad637f16d2059757a908fceaf7076',  $reflectionService_reference, 'TYPO3\Flow\Reflection\ReflectionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'); });
			}
		}
	}
}
#