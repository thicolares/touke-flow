<?php
namespace TYPO3\Surf\Application\TYPO3;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * An "application" which does bundle Flow or similar distributions.
 *
 */
class FlowDistribution_Original extends \TYPO3\Surf\Application\TYPO3\Flow {

	/**
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('TYPO3 Flow Distribution');
		$this->setOption('tagRecurseIntoSubmodules', TRUE);
	}

	/**
	 * Register tasks for this application
	 *
	 * @param \TYPO3\Surf\Domain\Model\Workflow $workflow
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @return void
	 */
	public function registerTasks(Workflow $workflow, Deployment $deployment) {
		parent::registerTasks($workflow, $deployment);

		$this->checkIfMandatoryOptionsExist();
		$this->buildConfiguration();
		$this->defineTasks($workflow, $deployment);

		if ($this->getOption('enableTests') !== FALSE) {
			$workflow
				->addTask(array(
					'typo3.surf:typo3:flow:unittest',
					'typo3.surf:typo3:flow:functionaltest'
				), 'test', $this);
		}

		$workflow->addTask(array(
				'createZipDistribution',
				'createTarGzDistribution',
				'createTarBz2Distribution',
			), 'cleanup', $this);

		if ($this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			$workflow->addTask('typo3.surf:sourceforgeupload', 'cleanup', $this);
		}
		if ($this->hasOption('releaseHost')) {
			$workflow->addTask('typo3.surf:release:preparerelease', 'initialize', $this);
			$workflow->addTask('typo3.surf:release:release', 'cleanup', $this);
		}
		if ($this->hasOption('releaseHost') && $this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			$workflow->addTask('typo3.surf:release:adddownload', 'cleanup', $this);
		}
		if ($this->hasOption('createTags') && $this->getOption('createTags') === TRUE) {
			$workflow->addTask('typo3.surf:git:tag', 'cleanup', $this);
			if ($this->hasOption('pushTags') && $this->getOption('pushTags') === TRUE) {
				$workflow->afterTask('typo3.surf:git:tag', 'pushTags', $this);
			}
		}

		$workflow->removeTask('typo3.surf:typo3:flow:migrate');
	}

	/**
	 * Check if all necessary options to run are set
	 *
	 * @return void
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 */
	protected function checkIfMandatoryOptionsExist() {
		if (!$this->hasOption('version')) {
			throw new InvalidConfigurationException('"version" option needs to be defined. Example: 1.0.0-beta2', 1314187396);
		}
		if (!$this->hasOption('projectName')) {
			throw new InvalidConfigurationException('"projectName" option needs to be defined. Example: TYPO3 Flow', 1314187397);
		}

		if ($this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			if (!$this->hasOption('sourceforgeProjectName')) {
				throw new InvalidConfigurationException('"sourceforgeProjectName" option needs to be specified', 1314187402);
			}
			if (!$this->hasOption('sourceforgePackageName')) {
				throw new InvalidConfigurationException('"sourceforgePackageName" option needs to be specified', 1314187406);
			}
			if (!$this->hasOption('sourceforgeUserName')) {
				throw new InvalidConfigurationException('"sourceforgeUserName" option needs to be specified', 1314187407);
			}
		}

		if ($this->hasOption('releaseHost')) {
			if (!$this->hasOption('releaseHostSitePath')) {
				throw new InvalidConfigurationException('"releaseHostSitePath" option needs to be specified', 1321545975);
			}
		}
		if ($this->hasOption('releaseHost') && $this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			if (!$this->hasOption('releaseDownloadLabel')) {
				throw new InvalidConfigurationException('"releaseDownloadLabel" option needs to be specified', 1321545965);
			}
			if (!$this->hasOption('releaseDownloadUriPattern')) {
				throw new InvalidConfigurationException('"releaseDownloadUriPattern" option needs to be specified', 1321545985);
			}
		}
	}

	/**
	 * Build configuration which we need later into $this->configuration
	 *
	 * @return void
	 */
	protected function buildConfiguration() {
		$versionAndProjectName = sprintf('%s-%s', str_replace(' ', '_', $this->getOption('projectName')), $this->getOption('version'));
		$this->configuration['versionAndProjectName'] = $versionAndProjectName;

		$this->configuration['zipFile'] = $this->getDeploymentPath() . '/buildArtifacts/' . $versionAndProjectName . '.zip';
		$this->configuration['tarGzFile'] = $this->getDeploymentPath() . '/buildArtifacts/' . $versionAndProjectName . '.tar.gz';
		$this->configuration['tarBz2File'] = $this->getDeploymentPath() . '/buildArtifacts/' . $versionAndProjectName . '.tar.bz2';
	}

	/**
	 * Configure tasks
	 *
	 * @param Workflow $workflow
	 * @param Deployment $deployment
	 * @return void
	 */
	protected function defineTasks(Workflow $workflow, Deployment $deployment) {
		$excludePatterns = array(
			'.git*',
			'Data/*',
			'Web/_Resources/*',
			'Build/Reports',
			'./Cache',
			'Configuration/PackageStates.php'
		);

		$baseArchiveConfiguration = array(
			'sourceDirectory' => $deployment->getApplicationReleasePath($this),
			'baseDirectory' => $this->configuration['versionAndProjectName'],
			'exclude' => $excludePatterns
		);

		$workflow->defineTask('createZipDistribution', 'typo3.surf:createArchive', array_merge($baseArchiveConfiguration, array(
			'targetFile' => $this->configuration['zipFile']
		)));

		$workflow->defineTask('createTarGzDistribution', 'typo3.surf:createArchive', array_merge($baseArchiveConfiguration, array(
			'targetFile' => $this->configuration['tarGzFile'],
		)));

		$workflow->defineTask('createTarBz2Distribution', 'typo3.surf:createArchive', array_merge($baseArchiveConfiguration, array(
			'targetFile' => $this->configuration['tarBz2File'],
		)));

		if ($this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			$workflow->defineTask('typo3.surf:sourceforgeupload', 'typo3.surf:sourceforgeupload', array(
				'sourceforgeProjectName' => $this->getOption('sourceforgeProjectName'),
				'sourceforgePackageName' => $this->getOption('sourceforgePackageName'),
				'sourceforgeUserName' => $this->getOption('sourceforgeUserName'),
				'version' => $this->getOption('version'),
				'files' => array(
					$this->configuration['zipFile'],
					$this->configuration['tarGzFile'],
					$this->configuration['tarBz2File'],
				)
			));
		}

		if ($this->hasOption('releaseHost')) {
			$workflow->defineTask('typo3.surf:release:preparerelease', 'typo3.surf:release:preparerelease', array(
				'releaseHost' =>  $this->getOption('releaseHost'),
				'releaseHostSitePath' => $this->getOption('releaseHostSitePath'),
				'releaseHostLogin' =>  $this->hasOption('releaseHostLogin') ? $this->getOption('releaseHostLogin') : NULL,
				'productName' => $this->getOption('projectName'),
				'version' => $this->getOption('version'),
			));
			$workflow->defineTask('typo3.surf:release:release', 'typo3.surf:release:release', array(
				'releaseHost' =>  $this->getOption('releaseHost'),
				'releaseHostSitePath' => $this->getOption('releaseHostSitePath'),
				'releaseHostLogin' =>  $this->hasOption('releaseHostLogin') ? $this->getOption('releaseHostLogin') : NULL,
				'productName' => $this->getOption('projectName'),
				'version' => $this->getOption('version'),
				'changeLogUri' =>  $this->hasOption('changeLogUri') ? $this->getOption('changeLogUri') : NULL,
			));
		}

		if ($this->hasOption('releaseHost') && $this->hasOption('enableSourceforgeUpload') && $this->getOption('enableSourceforgeUpload') === TRUE) {
			$workflow->defineTask('typo3.surf:release:adddownload', 'typo3.surf:release:adddownload', array(
				'releaseHost' =>  $this->getOption('releaseHost'),
				'releaseHostSitePath' => $this->getOption('releaseHostSitePath'),
				'releaseHostLogin' =>  $this->hasOption('releaseHostLogin') ? $this->getOption('releaseHostLogin') : NULL,
				'productName' => $this->getOption('projectName'),
				'version' => $this->getOption('version'),
				'label' => $this->getOption('releaseDownloadLabel'),
				'downloadUriPattern' => $this->getOption('releaseDownloadUriPattern'),
				'files' => array(
					$this->configuration['zipFile'],
					$this->configuration['tarGzFile'],
					$this->configuration['tarBz2File'],
				)
			));
		}

		$workflow->defineTask('typo3.surf:git:tag', 'typo3.surf:git:tag', array(
			'tagName' => $this->configuration['versionAndProjectName'],
			'description' => 'Tag distribution with tag ' . $this->configuration['versionAndProjectName'],
			'recurseIntoSubmodules' => $this->getOption('tagRecurseIntoSubmodules')
		));

		$workflow->defineTask('pushTags', 'typo3.surf:git:push', array(
			'remote' => 'origin',
			'refspec' => $this->configuration['versionAndProjectName'] . ':refs/tags/' . $this->configuration['versionAndProjectName'],
			'recurseIntoSubmodules' => $this->getOption('tagRecurseIntoSubmodules')
		));
	}
}
namespace TYPO3\Surf\Application\TYPO3;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An "application" which does bundle Flow or similar distributions.
 */
class FlowDistribution extends FlowDistribution_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


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
	$reflectedClass = new \ReflectionClass('TYPO3\Surf\Application\TYPO3\FlowDistribution');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Surf\Application\TYPO3\FlowDistribution', $propertyName, 'transient')) continue;
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