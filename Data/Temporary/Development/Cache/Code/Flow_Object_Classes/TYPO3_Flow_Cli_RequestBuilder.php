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

use TYPO3\Flow\Cli\Command;
use TYPO3\Flow\Cli\CommandManager;

use TYPO3\Flow\Annotations as Flow;

/**
 * Builds a CLI request object from the raw command call
 *
 * @Flow\Scope("singleton")
 */
class RequestBuilder_Original {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var CommandManager
	 */
	protected $commandManager;

	/**
	 * @param \TYPO3\Flow\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\Flow\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\Flow\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param CommandManager $commandManager
	 * @return void
	 */
	public function injectCommandManager(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Builds a CLI request object from a command line.
	 *
	 * The given command line may be a string (e.g. "mypackage:foo do-that-thing --force") or
	 * an array consisting of the individual parts. The array must not include the script
	 * name (like in $argv) but start with command right away.
	 *
	 * @param mixed $commandLine The command line, either as a string or as an array
	 * @return \TYPO3\Flow\Cli\Request The CLI request as an object
	 */
	public function build($commandLine) {
		$request = new Request();
		$request->setControllerObjectName('TYPO3\Flow\Command\HelpCommandController');

		$rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
		if (count($rawCommandLineArguments) === 0) {
			$request->setControllerCommandName('helpStub');
			return $request;
		}
		$commandIdentifier = trim(array_shift($rawCommandLineArguments));
		try {
			$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
		} catch (\TYPO3\Flow\Mvc\Exception\CommandException $exception) {
			$request->setArgument('exception', $exception);
			$request->setControllerCommandName('error');
			return $request;
		}
		$controllerObjectName = $this->objectManager->getObjectNameByClassName($command->getControllerClassName());
		$controllerCommandName = $command->getControllerCommandName();
		$request->setControllerObjectName($controllerObjectName);
		$request->setControllerCommandName($controllerCommandName);

		list($commandLineArguments, $exceedingCommandLineArguments) = $this->parseRawCommandLineArguments($rawCommandLineArguments, $controllerObjectName, $controllerCommandName);
		$request->setArguments($commandLineArguments);
		$request->setExceedingArguments($exceedingCommandLineArguments);

		return $request;
	}

	/**
	 * Takes an array of unparsed command line arguments and options and converts it separated
	 * by named arguments, options and unnamed arguments.
	 *
	 * @param array $rawCommandLineArguments The unparsed command parts (such as "--foo") as an array
	 * @param string $controllerObjectName Object name of the designated command controller
	 * @param string $controllerCommandName Command name of the recognized command (ie. method name without "Command" suffix)
	 * @return array All and exceeding command line arguments
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments, $controllerObjectName, $controllerCommandName) {
		$commandLineArguments = array();
		$exceedingArguments = array();
		$commandMethodName = $controllerCommandName . 'Command';
		$commandMethodParameters = $this->reflectionService->getMethodParameters($controllerObjectName, $commandMethodName);

		$requiredArguments = array();
		$optionalArguments = array();
		$argumentNames = array();
		foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
			$argumentNames[] = $parameterName;
			if ($parameterInfo['optional'] === FALSE) {
				$requiredArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			} else {
				$optionalArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			}
		}

		$decidedToUseNamedArguments = FALSE;
		$decidedToUseUnnamedArguments = FALSE;
		$argumentIndex = 0;
		while (count($rawCommandLineArguments) > 0) {

			$rawArgument = array_shift($rawCommandLineArguments);

			if ($rawArgument[0] === '-') {
				if ($rawArgument[1] === '-') {
					$rawArgument = substr($rawArgument, 2);
				} else {
					$rawArgument = substr($rawArgument, 1);
				}
				$argumentName = $this->extractArgumentNameFromCommandLinePart($rawArgument);

				if (isset($optionalArguments[$argumentName])) {
					$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $optionalArguments[$argumentName]['type']);
					$commandLineArguments[$optionalArguments[$argumentName]['parameterName']] = $argumentValue;
				} elseif(isset($requiredArguments[$argumentName])) {
					if ($decidedToUseUnnamedArguments) {
						throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
					}
					$decidedToUseNamedArguments = TRUE;
					$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $requiredArguments[$argumentName]['type']);
					$commandLineArguments[$requiredArguments[$argumentName]['parameterName']] = $argumentValue;
					unset($requiredArguments[$argumentName]);
				}
			} else {
				if (count($requiredArguments) > 0) {
					if ($decidedToUseNamedArguments) {
						throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
					}
					$argument = array_shift($requiredArguments);
					$commandLineArguments[$argument['parameterName']] = $rawArgument;
					$decidedToUseUnnamedArguments = TRUE;
				} else {
					$exceedingArguments[] = $rawArgument;
				}
			}
			$argumentIndex ++;
		}

		return array($commandLineArguments, $exceedingArguments);
	}

	/**
	 * Extracts the option or argument name from the name / value pair of a command line.
	 *
	 * @param string $commandLinePart Part of the command line, e.g. "my-important-option=SomeInterestingValue"
	 * @return string The lowercased argument name, e.g. "myimportantoption"
	 */
	protected function extractArgumentNameFromCommandLinePart($commandLinePart) {
		$nameAndValue = explode('=', $commandLinePart, 2);
		return strtolower(str_replace('-', '', $nameAndValue[0]));
	}

	/**
	 * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
	 *
	 * @param string $currentArgument The current argument
	 * @param array &$rawCommandLineArguments Array of the remaining command line arguments
	 * @param string $expectedArgumentType The expected type of the current argument, because booleans get special attention
	 * @return string The value of the first argument
	 */
	protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments, $expectedArgumentType) {
		if ((!isset($rawCommandLineArguments[0]) && (strpos($currentArgument, '=') === FALSE)) || (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === FALSE))) {
			return TRUE;
		}

		if (strpos($currentArgument, '=') === FALSE) {
			$possibleValue = trim(array_shift($rawCommandLineArguments));
			if (strpos($possibleValue, '=') === FALSE) {
				if ($expectedArgumentType !== 'boolean') {
					return $possibleValue;
				}
				if (array_search($possibleValue, array('on', '1', 'y', 'yes', 'true', 'TRUE')) !== FALSE) {
					return TRUE;
				}
				if (array_search($possibleValue, array('off', '0', 'n', 'no', 'false', 'FALSE')) !== FALSE) {
					return FALSE;
				}
				array_unshift($rawCommandLineArguments, $possibleValue);
				return TRUE;
			}
			$currentArgument .= $possibleValue;
		}

		$splitArgument = explode('=', $currentArgument, 2);
		while ((!isset($splitArgument[1]) || trim($splitArgument[1]) === '') && count($rawCommandLineArguments) > 0) {
			$currentArgument .= array_shift($rawCommandLineArguments);
			$splitArgument = explode('=', $currentArgument);
		}

		$value = (isset($splitArgument[1])) ? $splitArgument[1] : '';
		return $value;
	}

}
namespace TYPO3\Flow\Cli;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Builds a CLI request object from the raw command call
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class RequestBuilder extends RequestBuilder_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Cli\RequestBuilder') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Cli\RequestBuilder', $this);
		if ('TYPO3\Flow\Cli\RequestBuilder' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Cli\RequestBuilder') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Cli\RequestBuilder', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Cli\RequestBuilder');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Cli\RequestBuilder', $propertyName, 'transient')) continue;
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
		$this->injectEnvironment(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment'));
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectPackageManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Package\PackageManagerInterface'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$this->injectCommandManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Cli\CommandManager'));
	}
}
#