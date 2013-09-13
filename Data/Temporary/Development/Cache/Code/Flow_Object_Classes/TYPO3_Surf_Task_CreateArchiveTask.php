<?php
namespace TYPO3\Surf\Task;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Exception\InvalidConfigurationException;

use TYPO3\Flow\Annotations as Flow;

/**
 * A task for creating an zip / tar.gz / tar.bz2 archive.
 * Needs the following options:
 *
 * - sourceDirectory -- directory which should be compressed
 * - targetFile -- target file. The file ending defines the format. Supported are .zip, .tar.gz, .tar.bz2
 * - baseDirectory -- base directory in the compressed archive in which all files should reside in.
 * - exclude -- an array of exclude patterns, as being understood by tar.
 *
 * This task needs the following unix command line tools:
 * - tar / gnutar
 * - zip
 *
 */
class CreateArchiveTask_Original extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->checkOptionsForValidity($options);

		$this->shell->execute('rm -f ' . $options['targetFile'] . '; mkdir -p ' . dirname($options['targetFile']), $node, $deployment);
		$sourcePath = $deployment->getApplicationReleasePath($application);

		$tarOptions = sprintf(' --transform="s,^%s,%s," ', ltrim($sourcePath, '/'), $options['baseDirectory']);
		if (isset($options['exclude']) && is_array($options['exclude'])) {
			foreach ($options['exclude'] as $excludePattern) {
				$tarOptions .= sprintf(' --exclude="%s" ', $excludePattern);
			}
		}

		if (substr($options['targetFile'], -7) === '.tar.gz') {
			$tarOptions .= sprintf(' -czf %s %s', $options['targetFile'], $sourcePath);
			$this->shell->execute(sprintf('tar %s || gnutar %s', $tarOptions, $tarOptions), $node, $deployment);

		} elseif (substr($options['targetFile'], -8) === '.tar.bz2') {

			$tarOptions .= sprintf(' -cjf %s %s', $options['targetFile'], $sourcePath);
			$this->shell->execute(sprintf('tar %s || gnutar %s', $tarOptions, $tarOptions), $node, $deployment);

		} elseif (substr($options['targetFile'], -4) === '.zip') {

			$temporaryDirectory = sys_get_temp_dir() . '/' . uniqid('f3_deploy');
			$this->shell->execute(sprintf('mkdir -p %s', $temporaryDirectory), $node, $deployment);
			$tarOptions .= sprintf(' -cf %s/out.tar %s', $temporaryDirectory, $sourcePath);
			$this->shell->execute(sprintf('tar %s || gnutar %s', $tarOptions, $tarOptions), $node, $deployment);
			$this->shell->execute(sprintf('cd %s; tar -xf out.tar; rm out.tar; zip --quiet -9 -r out %s', $temporaryDirectory, $options['baseDirectory']), $node, $deployment);
			$this->shell->execute(sprintf('mv %s/out.zip %s; rm -Rf %s', $temporaryDirectory, $options['targetFile'], $temporaryDirectory), $node, $deployment);

		} else {
			throw new \TYPO3\Surf\Exception\TaskExecutionException('Unknown target file format', 1314248387);
		}
	}

	/**
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 */
	protected function checkOptionsForValidity(array $options) {
		if (!isset($options['sourceDirectory']) || !is_dir($options['sourceDirectory'])) {
			throw new InvalidConfigurationException('sourceDirectory not configured', 1314187354);
		}

		if (!isset($options['targetFile'])) {
			throw new InvalidConfigurationException('targetFile not configured', 1314187356);
		}
		if (!preg_match('/\.(tar\.gz|tar\.bz2|zip)$/', $options['targetFile'])) {
			throw new InvalidConfigurationException('targetFile only with file ending tar.gz, tar.bz2 or zip supported, given: "' . $options['targetFile'] . '"!', 1314187359);
		}

		if (!isset($options['baseDirectory'])) {
			throw new InvalidConfigurationException('baseDirectory not configured', 1314187361);
		}
	}

}
namespace TYPO3\Surf\Task;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A task for creating an zip / tar.gz / tar.bz2 archive.
 * Needs the following options:
 * 
 * - sourceDirectory -- directory which should be compressed
 * - targetFile -- target file. The file ending defines the format. Supported are .zip, .tar.gz, .tar.bz2
 * - baseDirectory -- base directory in the compressed archive in which all files should reside in.
 * - exclude -- an array of exclude patterns, as being understood by tar.
 * 
 * This task needs the following unix command line tools:
 * - tar / gnutar
 * - zip
 */
class CreateArchiveTask extends CreateArchiveTask_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Surf\Task\CreateArchiveTask' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Task\CreateArchiveTask');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Task\CreateArchiveTask', $propertyName, 'transient')) continue;
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
		$this->shell = new \TYPO3\Surf\Domain\Service\ShellCommandService();
	}
}
#