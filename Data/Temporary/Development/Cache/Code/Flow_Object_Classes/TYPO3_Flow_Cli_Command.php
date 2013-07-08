<?php
namespace TYPO3\Flow\Cli;

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
 * Represents a Command
 *
 */
class Command_Original {

	/**
	 * @var string
	 */
	protected $controllerClassName;

	/**
	 * @var string
	 */
	protected $controllerCommandName;

	/**
	 * @var string
	 */
	protected $commandIdentifier;

	/**
	 * @var \TYPO3\Flow\Reflection\MethodReflection
	 */
	protected $commandMethodReflection;

	/**
	 * Reflection service
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	private $reflectionService;

	/**
	 * Constructor
	 *
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
	 * @throws \InvalidArgumentException
	 */
	public function __construct($controllerClassName, $controllerCommandName) {
		$this->controllerClassName = $controllerClassName;
		$this->controllerCommandName = $controllerCommandName;

		$matchCount = preg_match('/^(?P<PackageNamespace>\w+(?:\\\\\w+)*)\\\\Command\\\\(?P<ControllerName>\w+)CommandController$/', $controllerClassName, $matches);
		if ($matchCount !== 1) {
			throw new \InvalidArgumentException('Invalid controller class name "' . $controllerClassName . '". Make sure your controller is in a folder named "Command" and it\'s name ends in "CommandController"', 1305100019);
		}

		$this->commandIdentifier = strtolower(str_replace('\\', '.', $matches['PackageNamespace']) . ':' . $matches['ControllerName'] . ':' . $controllerCommandName);
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService Reflection service
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @return string
	 */
	public function getControllerClassName() {
		return $this->controllerClassName;
	}

	/**
	 * @return string
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Returns the command identifier for this command
	 *
	 * @return string The command identifier for this command, following the pattern packagekey:controllername:commandname
	 */
	public function getCommandIdentifier() {
		return $this->commandIdentifier;
	}

	/**
	 * Returns a short description of this command
	 *
	 * @return string A short description
	 */
	public function getShortDescription() {
		$lines = explode(chr(10), $this->getCommandMethodReflection()->getDescription());
		return (count($lines) > 0) ? trim($lines[0]) : '<no description available>';
	}

	/**
	 * Returns a longer description of this command
	 * This is the complete method description except for the first line which can be retrieved via getShortDescription()
	 * If The command description only consists of one line, an empty string is returned
	 *
	 * @return string A longer description of this command
	 */
	public function getDescription() {
		$lines = explode(chr(10), $this->getCommandMethodReflection()->getDescription());
		array_shift($lines);
		$descriptionLines = array();
		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if ($descriptionLines !== array() || $trimmedLine !== '') {
				$descriptionLines[] = $trimmedLine;
			}
		}
		return implode(chr(10), $descriptionLines);
	}

	/**
	 * Returns TRUE if this command expects required and/or optional arguments, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function hasArguments() {
		return count($this->getCommandMethodReflection()->getParameters()) > 0;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Cli\CommandArgumentDefinition that contains
	 * information about required/optional arguments of this command.
	 * If the command does not expect any arguments, an empty array is returned
	 *
	 * @return array<\TYPO3\Flow\Cli\CommandArgumentDefinition>
	 */
	public function getArgumentDefinitions() {
		if (!$this->hasArguments()) {
			return array();
		}
		$commandArgumentDefinitions = array();
		$commandMethodReflection = $this->getCommandMethodReflection();
		$annotations = $commandMethodReflection->getTagsValues();
		$commandParameters = $this->reflectionService->getMethodParameters($this->controllerClassName, $this->controllerCommandName . 'Command');
		$i = 0;
		foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
			$explodedAnnotation = explode(' ', $annotations['param'][$i]);
			array_shift($explodedAnnotation);
			array_shift($explodedAnnotation);
			$description = implode(' ', $explodedAnnotation);
			$required = $commandParameterDefinition['optional'] !== TRUE;
			$commandArgumentDefinitions[] = new CommandArgumentDefinition($commandParameterName, $required, $description);
			$i ++;
		}
		return $commandArgumentDefinitions;
	}

	/**
	 * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
	 * Internal commands are still accessible through the regular command line interface, but should not be used
	 * by users.
	 *
	 * @return boolean
	 */
	public function isInternal() {
		return $this->getCommandMethodReflection()->isTaggedWith('internal');
	}

	/**
	 * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
	 *
	 * Note that neither this method nor the @Flow\FlushesCaches annotation is currently part of the official API.
	 *
	 * @return boolean
	 */
	public function isFlushingCaches() {
		return $this->getCommandMethodReflection()->isTaggedWith('flushescaches');
	}

	/**
	 * Returns an array of command identifiers which were specified in the "@see"
	 * annotation of a command method.
	 *
	 * @return array
	 */
	public function getRelatedCommandIdentifiers() {
		$commandMethodReflection = $this->getCommandMethodReflection();
		if (!$commandMethodReflection->isTaggedWith('see')) {
			return array();
		}

		$relatedCommandIdentifiers = array();
		foreach ($commandMethodReflection->getTagValues('see') as $tagValue) {
			if (preg_match('/^[\w\d\.]+:[\w\d]+:[\w\d]+$/', $tagValue) === 1) {
				$relatedCommandIdentifiers[] = $tagValue;
			}
		}
		return $relatedCommandIdentifiers;
	}

	/**
	 * @return \TYPO3\Flow\Reflection\MethodReflection
	 */
	protected function getCommandMethodReflection() {
		if ($this->commandMethodReflection === NULL) {
			$this->commandMethodReflection = new \TYPO3\Flow\Reflection\MethodReflection($this->controllerClassName, $this->controllerCommandName . 'Command');
		}
		return $this->commandMethodReflection;
	}
}
namespace TYPO3\Flow\Cli;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Represents a Command
 */
class Command extends Command_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
	 * @throws \InvalidArgumentException
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = NULL;
		if (!array_key_exists(1, $arguments)) $arguments[1] = NULL;
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $controllerClassName in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		if (!array_key_exists(1, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $controllerCommandName in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Cli\Command' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Cli\Command');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Cli\Command', $propertyName, 'transient')) continue;
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
	}
}
#