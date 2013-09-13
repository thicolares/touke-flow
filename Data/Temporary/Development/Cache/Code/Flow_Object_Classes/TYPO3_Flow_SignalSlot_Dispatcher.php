<?php
namespace TYPO3\Flow\SignalSlot;

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
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Dispatcher_Original {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Information about all slots connected a certain signal.
	 * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
	 * array of information about the slot
	 * @var array
	 */
	protected $slots = array();

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Connects a signal with a slot.
	 * One slot can be connected with multiple signals by calling this method multiple times.
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
	 * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
	 * @param boolean $passSignalInformation If set to TRUE, the last argument passed to the slot will be information about the signal (EmitterClassName::signalName)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function connect($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName = '', $passSignalInformation = TRUE) {
		$class = NULL;
		$object = NULL;

		if (strpos($signalName, 'emit') === 0) {
			$possibleSignalName = lcfirst(substr($signalName, strlen('emit')));
			throw new \InvalidArgumentException('The signal should not be connected with the method name ("' . $signalName .  '"). Try "' . $possibleSignalName . '" for the signal name.', 1314016630);
		}

		if (is_object($slotClassNameOrObject)) {
			$object = $slotClassNameOrObject;
			$method = ($slotClassNameOrObject instanceof \Closure) ? '__invoke' : $slotMethodName;
		} else {
			if ($slotMethodName === '') throw new \InvalidArgumentException('The slot method name must not be empty (except for closures).', 1229531659);
			$class = $slotClassNameOrObject;
			$method = $slotMethodName;
		}

		$this->slots[$signalClassName][$signalName][] = array(
			'class' => $class,
			'method' => $method,
			'object' => $object,
			'passSignalInformation' => ($passSignalInformation === TRUE)
		);
	}

	/**
	 * Dispatches a signal by calling the registered Slot methods
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param array $signalArguments arguments passed to the signal method
	 * @return void
	 * @throws \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException if the slot is not valid
	 * @api
	 */
	public function dispatch($signalClassName, $signalName, array $signalArguments = array()) {
		if (!isset($this->slots[$signalClassName][$signalName])) {
			return;
		}

		foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
			if (isset($slotInformation['object'])) {
				$object = $slotInformation['object'];
			} elseif (substr($slotInformation['method'], 0, 2) === '::') {
				if (!isset($this->objectManager)) {
					if (is_callable($slotInformation['class'] . $slotInformation['method'])) {
						$object = $slotInformation['class'];
					} else {
						throw new \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException(sprintf('Cannot dispatch %s::%s to class %s. The object manager is not yet available in the Signal Slot Dispatcher and therefore it cannot dispatch classes.', $signalClassName, $signalName, $slotInformation['class']), 1298113624);
					}
				} else {
					$object = $this->objectManager->getClassNameByObjectName($slotInformation['class']);
				}
				$slotInformation['method'] = substr($slotInformation['method'], 2);
			} else {
				if (!isset($this->objectManager)) {
					throw new \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException(sprintf('Cannot dispatch %s::%s to class %s. The object manager is not yet available in the Signal Slot Dispatcher and therefore it cannot dispatch classes.', $signalClassName, $signalName, $slotInformation['class']), 1298113624);
				}
				if (!$this->objectManager->isRegistered($slotInformation['class'])) {
					throw new \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException('The given class "' . $slotInformation['class'] . '" is not a registered object.', 1245673367);
				}
				$object = $this->objectManager->get($slotInformation['class']);
			}
			if ($slotInformation['passSignalInformation'] === TRUE) {
				$signalArguments[] = $signalClassName . '::' . $signalName;
			}
			if (!method_exists($object, $slotInformation['method'])) {
				throw new \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException('The slot method ' . get_class($object) . '->' . $slotInformation['method'] . '() does not exist.', 1245673368);
			}
			call_user_func_array(array($object, $slotInformation['method']), $signalArguments);
		}
	}

	/**
	 * Returns all slots which are connected with the given signal
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @return array An array of arrays with slot information
	 * @api
	 */
	public function getSlots($signalClassName, $signalName) {
		return (isset($this->slots[$signalClassName][$signalName])) ? $this->slots[$signalClassName][$signalName] : array();
	}

	/**
	 * Returns all signals with its slots
	 *
	 * @return array An array of arrays with slot information
	 * @api
	 */
	public function getSignals() {
		return $this->slots;
	}

}
namespace TYPO3\Flow\SignalSlot;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class Dispatcher extends Dispatcher_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\SignalSlot\Dispatcher') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\SignalSlot\Dispatcher', $this);
		if ('TYPO3\Flow\SignalSlot\Dispatcher' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\SignalSlot\Dispatcher') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\SignalSlot\Dispatcher', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\SignalSlot\Dispatcher');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\SignalSlot\Dispatcher', $propertyName, 'transient')) continue;
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
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
	}
}
#