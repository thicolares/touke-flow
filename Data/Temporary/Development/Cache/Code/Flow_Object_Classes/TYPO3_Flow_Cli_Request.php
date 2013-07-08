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

use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Cli\Command;

/**
 * Represents a CLI request.
 *
 * @api
 */
class Request_Original implements RequestInterface {

	/**
	 * @var string
	 */
	protected $controllerObjectName;

	/**
	 * @var string
	 */
	protected $controllerCommandName = 'default';

	/**
	 * @var \TYPO3\Flow\Cli\Command
	 */
	protected $command;

	/**
	 * The arguments for this request
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * @var array
	 */
	protected $exceedingArguments = array();

	/**
	 * If this request has been changed and needs to be dispatched again
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 *
	 * @var array
	 */
	protected $commandLineArguments;

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been dispatched successfully
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Sets the object name of the controller
	 *
	 * @param string $controllerObjectName Object name of the controller which processes this request
	 * @return void
	 */
	public function setControllerObjectName($controllerObjectName) {
		$this->controllerObjectName = $controllerObjectName;
		$this->command = NULL;
	}

	/**
	 * Returns the object name of the controller
	 *
	 * @return string The controller's object name
	 */
	public function getControllerObjectName() {
		return $this->controllerObjectName;
	}

	/**
	 * Returns the this request, as CLI request nesting is not supported.
	 *
	 * @return \TYPO3\Flow\Cli\Request
	 * @api
	 */
	public function getMainRequest() {
		return $this;
	}

	/**
	 * Returns true, as CLI request nesting is not supported.
	 *
	 * @return boolean
	 * @api
	 */
	public function isMainRequest() {
		return TRUE;
	}

	/**
	 * Sets the name of the command contained in this request.
	 *
	 * Note that the command name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $commandName Name of the command to execute by the controller
	 * @return void
	 */
	public function setControllerCommandName($commandName) {
		$this->controllerCommandName = $commandName;
		$this->command = NULL;
	}

	/**
	 * Returns the name of the command the controller is supposed to execute.
	 *
	 * @return string Command name
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Returns the command object for this request
	 *
	 * @return \TYPO3\Flow\Cli\Command
	 */
	public function getCommand() {
		if ($this->command === NULL) {
			$this->command = new Command($this->controllerObjectName, $this->controllerCommandName);
		}
		return $this->command;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || $argumentName === '') throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException('Invalid argument name.', 1300893885);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments array and therefore replaces any arguments which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @throws \TYPO3\Flow\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \TYPO3\Flow\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1300893886);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Sets the exceeding arguments
	 *
	 * @param array $exceedingArguments Numeric array of exceeding arguments
	 * @return void
	 */
	public function setExceedingArguments(array $exceedingArguments) {
		$this->exceedingArguments = $exceedingArguments;
	}

	/**
	 * Returns additional unnamed arguments (if any) which have been passed through the command line after all
	 * required arguments (if any) have been specified.
	 *
	 * For a command method with the signature ($argument1, $argument2) and for the command line
	 * ./flow acme:foo --argument1 Foo --argument2 Bar baz quux
	 * this method would return array(0 => 'baz', 1 => 'quux')
	 *
	 * @return array Numeric array of exceeding argument values
	 */
	public function getExceedingArguments() {
		return $this->exceedingArguments;
	}

}
namespace TYPO3\Flow\Cli;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Represents a CLI request.
 */
class Request extends Request_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Cli\Request');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Cli\Request', $propertyName, 'transient')) continue;
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