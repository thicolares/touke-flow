<?php
namespace TYPO3\Fluid\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * View helper which renders the flash messages (if there are any) as an unsorted list.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:flashMessages />
 * </code>
 * <output>
 * <ul>
 *   <li class="flashmessages-ok">Some Default Message</li>
 *   <li class="flashmessages-warning">Some Warning Message</li>
 * </ul>
 * </output>
 * Depending on the FlashMessages
 *
 * <code title="Output with css class">
 * <f:flashMessages class="specialClass" />
 * </code>
 * <output>
 * <ul class="specialClass">
 *   <li class="specialClass-ok">Default Message</li>
 *   <li class="specialClass-notice"><h3>Some notice message</h3>With message title</li>
 * </ul>
 * </output>
 *
 * <code title="Output flash messages as a list, with arguments and filtered by a severity">
 * <f:flashMessages severity="Warning" as="flashMessages">
 * 	<dl class="messages">
 * 	<f:for each="{flashMessages}" as="flashMessage">
 * 		<dt>{flashMessage.code}</dt>
 * 		<dd>{flashMessage}</dd>
 * 	</f:for>
 * 	</dl>
 * </f:flashMessages>
 * </code>
 * <output>
 * <dl class="messages">
 * 	<dt>1013</dt>
 * 	<dd>Some Warning Message.</dd>
 * </dl>
 * </output>
 *
 * @api
 */
class FlashMessagesViewHelper_Original extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'ul';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders flash messages that have been added to the FlashMessageContainer in previous request(s).
	 *
	 * @param string $as The name of the current flashMessage variable for rendering inside
	 * @param string $severity severity of the messages (One of the \TYPO3\Flow\Error\Message::SEVERITY_* constants)
	 * @return string rendered Flash Messages, if there are any.
	 * @api
	 */
	public function render($as = NULL, $severity = NULL) {
		$flashMessages = $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush($severity);
		if (count($flashMessages) < 1) {
			return '';
		}
		if ($as === NULL) {
			$content = $this->renderAsList($flashMessages);
		} else {
			$content = $this->renderFromTemplate($flashMessages, $as);
		}
		return $content;
	}

	/**
	 * Render the flash messages as unsorted list. This is triggered if no "as" argument is given
	 * to the ViewHelper.
	 *
	 * @param array $flashMessages
	 * @return string
	 */
	protected function renderAsList(array $flashMessages) {
		$flashMessagesClass = $this->arguments['class'] !== NULL ? $this->arguments['class'] : 'flashmessages';
		$tagContent = '';
		foreach ($flashMessages as $singleFlashMessage) {
			$severityClass = sprintf('%s-%s', $flashMessagesClass, strtolower($singleFlashMessage->getSeverity()));
			$messageContent = htmlspecialchars($singleFlashMessage->render());
			if ($singleFlashMessage->getTitle() !== '') {
				$messageContent = sprintf('<h3>%s</h3>', htmlspecialchars($singleFlashMessage->getTitle())) . $messageContent;
			}
			$tagContent .= sprintf('<li class="%s">%s</li>', htmlspecialchars($severityClass), $messageContent);
		}
		$this->tag->setContent($tagContent);
		$content = $this->tag->render();

		return $content;
	}

	/**
	 * Defer the rendering of Flash Messages to the template. In this case,
	 * the flash messages are stored in the template inside the variable specified
	 * in "as".
	 *
	 * @param array $flashMessages
	 * @param string $as
	 * @return string
	 */
	protected function renderFromTemplate(array $flashMessages, $as) {
		$templateVariableContainer = $this->renderingContext->getTemplateVariableContainer();
		$templateVariableContainer->add($as, $flashMessages);
		$content = $this->renderChildren();
		$templateVariableContainer->remove($as);

		return $content;
	}
}
namespace TYPO3\Fluid\ViewHelpers;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * View helper which renders the flash messages (if there are any) as an unsorted list.
 * 
 * = Examples =
 * 
 * <code title="Simple">
 * <f:flashMessages />
 * </code>
 * <output>
 * <ul>
 *   <li class="flashmessages-ok">Some Default Message</li>
 *   <li class="flashmessages-warning">Some Warning Message</li>
 * </ul>
 * </output>
 * Depending on the FlashMessages
 * 
 * <code title="Output with css class">
 * <f:flashMessages class="specialClass" />
 * </code>
 * <output>
 * <ul class="specialClass">
 *   <li class="specialClass-ok">Default Message</li>
 *   <li class="specialClass-notice"><h3>Some notice message</h3>With message title</li>
 * </ul>
 * </output>
 * 
 * <code title="Output flash messages as a list, with arguments and filtered by a severity">
 * <f:flashMessages severity="Warning" as="flashMessages">
 * 	<dl class="messages">
 * 	<f:for each="{flashMessages}" as="flashMessage">
 * 		<dt>{flashMessage.code}</dt>
 * 		<dd>{flashMessage}</dd>
 * 	</f:for>
 * 	</dl>
 * </f:flashMessages>
 * </code>
 * <output>
 * <dl class="messages">
 * 	<dt>1013</dt>
 * 	<dd>Some Warning Message.</dd>
 * </dl>
 * </output>
 */
class FlashMessagesViewHelper extends FlashMessagesViewHelper_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		parent::__construct();
		if ('TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper', $propertyName, 'transient')) continue;
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
		$this->injectTagBuilder(new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('', ''));
		$this->injectObjectManager(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Object\ObjectManagerInterface'));
		$this->injectSystemLogger(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Log\SystemLoggerInterface'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
	}
}
#