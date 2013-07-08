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
 * Command controller for tasks related to Doctrine
 *
 * @Flow\Scope("singleton")
 */
class DoctrineCommandController_Original extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

	/**
	 * Injects the Flow settings, only the persistence part is kept for further use
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Compile the Doctrine proxy classes
	 *
	 * @return void
	 * @Flow\Internal
	 */
	public function compileProxiesCommand() {
		$this->doctrineService->compileProxies();
	}

	/**
	 * Validate the class/table mappings
	 *
	 * Checks if the current class model schema is valid. Any inconsistencies
	 * in the relations between models (for example caused by wrong or
	 * missing annotations) will be reported.
	 *
	 * Note that this does not check the table structure in the database in
	 * any way.
	 *
	 * @return void
	 * @see typo3.flow:doctrine:entitystatus
	 */
	public function validateCommand() {
		$this->outputLine();
		$classesAndErrors = $this->doctrineService->validateMapping();
		if (count($classesAndErrors) === 0) {
			$this->outputLine('Mapping validation passed, no errors were found.');
		} else {
			$this->outputLine('Mapping validation FAILED!');
			foreach ($classesAndErrors as $className => $errors) {
				$this->outputLine('  %s', array($className));
				foreach ($errors as $errorMessage) {
					$this->outputLine('    %s', array($errorMessage));
				}
			}
			$this->quit(1);
		}
	}

	/**
	 * Create the database schema
	 *
	 * Creates a new database schema based on the current mapping information.
	 *
	 * It expects the database to be empty, if tables that are to be created already
	 * exist, this will lead to errors.
	 *
	 * @param string $output A file to write SQL to, instead of executing it
	 * @return void
	 * @see typo3.flow:doctrine:update
	 * @see typo3.flow:doctrine:migrate
	 */
	public function createCommand($output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->doctrineService->createSchema($output);
			if ($output === NULL) {
				$this->outputLine('Created database schema.');
			} else {
				$this->outputLine('Wrote schema creation SQL to file "' . $output . '".');
			}
		} else {
			$this->outputLine('Database schema creation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Update the database schema
	 *
	 * Updates the database schema without using existing migrations.
	 *
	 * It will not drop foreign keys, sequences and tables, unless <u>--unsafe-mode</u> is set.
	 *
	 * @param boolean $unsafeMode If set, foreign keys, sequences and tables can potentially be dropped.
	 * @param string $output A file to write SQL to, instead of executing the update directly
	 * @return void
	 * @see typo3.flow:doctrine:create
	 * @see typo3.flow:doctrine:migrate
	 */
	public function updateCommand($unsafeMode = FALSE, $output = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->doctrineService->updateSchema(!$unsafeMode, $output);
			if ($output === NULL) {
				$this->outputLine('Executed a database schema update.');
			} else {
				$this->outputLine('Wrote schema update SQL to file "' . $output . '".');
			}
		} else {
			$this->outputLine('Database schema update has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Show the current status of entities and mappings
	 *
	 * Shows basic information about which entities exist and possibly if their
	 * mapping information contains errors or not.
	 *
	 * To run a full validation, use the validate command.
	 *
	 * @param boolean $dumpMappingData If set, the mapping data will be output
	 * @return void
	 * @see typo3.flow:doctrine:validate
	 */
	public function entityStatusCommand($dumpMappingData = FALSE) {
		$info = $this->doctrineService->getEntityStatus();

		if ($info === array()) {
			$this->output('You do not have any mapped Doctrine ORM entities according to the current configuration. ');
			$this->outputLine('If you have entities or mapping files you should check your mapping configuration for errors.');
		} else {
			$this->outputLine('Found %d mapped entities:', array(count($info)));
			foreach ($info as $entityClassName => $entityStatus) {
				if ($entityStatus instanceof \Doctrine\Common\Persistence\Mapping\ClassMetadata) {
					$this->outputLine('[OK]   %s', array($entityClassName));
					if ($dumpMappingData) {
						\TYPO3\Flow\Error\Debugger::clearState();
						$this->outputLine(\TYPO3\Flow\Error\Debugger::renderDump($entityStatus, 0, TRUE, TRUE));
					}
				} else {
					$this->outputLine('[FAIL] %s', array($entityClassName));
					$this->outputLine($entityStatus);
					$this->outputLine();
				}
			}
		}
	}

	/**
	 * Run arbitrary DQL and display results
	 *
	 * Any DQL queries passed after the parameters will be executed, the results will be output:
	 *
	 * doctrine:dql --limit 10 'SELECT a FROM TYPO3\Flow\Security\Account a'
	 *
	 * @param integer $depth How many levels deep the result should be dumped
	 * @param string $hydrationMode One of: object, array, scalar, single-scalar, simpleobject
	 * @param integer $offset Offset the result by this number
	 * @param integer $limit Limit the result to this number
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function dqlCommand($depth = 3, $hydrationMode = 'array', $offset = NULL, $limit = NULL) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$dqlStatements = $this->request->getExceedingArguments();
			$hydrationModeConstant = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $hydrationMode));
			if (!defined($hydrationModeConstant)) {
				throw new \InvalidArgumentException('Hydration mode "' . $hydrationMode . '" does not exist. It should be either: object, array, scalar or single-scalar.');
			}

			foreach ($dqlStatements as $dql) {
				$resultSet = $this->doctrineService->runDql($dql, constant($hydrationModeConstant), $offset, $limit);
				\Doctrine\Common\Util\Debug::dump($resultSet, $depth);
			}
		} else {
			$this->outputLine('DQL query is not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Show the current migration status
	 *
	 * Displays the migration configuration as well as the number of
	 * available, executed and pending migrations.
	 *
	 * @return void
	 * @see typo3.flow:doctrine:migrate
	 * @see typo3.flow:doctrine:migrationexecute
	 * @see typo3.flow:doctrine:migrationgenerate
	 * @see typo3.flow:doctrine:migrationversion
	 */
	public function migrationStatusCommand() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->outputLine($this->doctrineService->getMigrationStatus());
		} else {
			$this->outputLine('Doctrine migration status not available, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Migrate the database schema
	 *
	 * Adjusts the database structure by applying the pending
	 * migrations provided by currently active packages.
	 *
	 * @param string $version The version to migrate to
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @param boolean $quiet If set, only the executed migration versions will be output, one per line
	 * @return void
	 * @see typo3.flow:doctrine:migrationstatus
	 * @see typo3.flow:doctrine:migrationexecute
	 * @see typo3.flow:doctrine:migrationgenerate
	 * @see typo3.flow:doctrine:migrationversion
	 */
	public function migrateCommand($version = NULL, $output = NULL, $dryRun = FALSE, $quiet = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$result = $this->doctrineService->executeMigrations($version, $output, $dryRun, $quiet);
			if ($result == '') {
				if (!$quiet) {
					$this->outputLine('No migration was necessary.');
				}
			} elseif ($output === NULL) {
				$this->outputLine($result);
			} else {
				if (!$quiet) {
					$this->outputLine('Wrote migration SQL to file "' . $output .'".');
				}
			}
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Execute a single migration
	 *
	 * Manually runs a single migration in the given direction.
	 *
	 * @param string $version The migration to execute
	 * @param string $direction Whether to execute the migration up (default) or down
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return void
	 * @see typo3.flow:doctrine:migrate
	 * @see typo3.flow:doctrine:migrationstatus
	 * @see typo3.flow:doctrine:migrationgenerate
	 * @see typo3.flow:doctrine:migrationversion
	 */
	public function migrationExecuteCommand($version, $direction = 'up', $output = NULL, $dryRun = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$this->outputLine($this->doctrineService->executeMigration($version, $direction, $output, $dryRun));
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Mark/unmark a migration as migrated
	 *
	 * If <u>all</u> is given as version, all available migrations are marked
	 * as requested.
	 *
	 * @param string $version The migration to execute
	 * @param boolean $add The migration to mark as migrated
	 * @param boolean $delete The migration to mark as not migrated
	 * @return void
	 * @throws \InvalidArgumentException
	 * @see typo3.flow:doctrine:migrate
	 * @see typo3.flow:doctrine:migrationstatus
	 * @see typo3.flow:doctrine:migrationexecute
	 * @see typo3.flow:doctrine:migrationgenerate
	 */
	public function migrationVersionCommand($version, $add = FALSE, $delete = FALSE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			if ($add === FALSE && $delete === FALSE) {
				throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
			}
			$this->doctrineService->markAsMigrated($version, $add ?: FALSE);
		} else {
			$this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

	/**
	 * Generate a new migration
	 *
	 * If $diffAgainstCurrent is TRUE (the default), it generates a migration file
	 * with the diff between current DB structure and the found mapping metadata.
	 *
	 * Otherwise an empty migration skeleton is generated.
	 *
	 * @param boolean $diffAgainstCurrent Whether to base the migration on the current schema structure
	 * @return void
	 * @see typo3.flow:doctrine:migrate
	 * @see typo3.flow:doctrine:migrationstatus
	 * @see typo3.flow:doctrine:migrationexecute
	 * @see typo3.flow:doctrine:migrationversion
	 */
	public function migrationGenerateCommand($diffAgainstCurrent = TRUE) {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['host'] !== NULL) {
			$migrationClassPathAndFilename = $this->doctrineService->generateMigration($diffAgainstCurrent);
			$this->outputLine('<u>Generated new migration class!</u>');
			$this->outputLine('');
			$this->outputLine('Next Steps:');
			$this->outputLine(sprintf('- Move <b>%s</b> to YourPackage/<b>Migrations/%s/</b>', $migrationClassPathAndFilename, $this->doctrineService->getDatabasePlatformName()));
			$this->outputLine('- Review and adjust the generated migration.');
			$this->outputLine('- (optional) execute the migration using <b>%s doctrine:migrate</b>', array($this->getFlowInvocationString()));
		} else {
			$this->outputLine('Doctrine migration generation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
			$this->quit(1);
		}
	}

}

namespace TYPO3\Flow\Command;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for tasks related to Doctrine
 * @\TYPO3\Flow\Annotations\Scope("singleton")
 */
class DoctrineCommandController extends DoctrineCommandController_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if (get_class($this) === 'TYPO3\Flow\Command\DoctrineCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\DoctrineCommandController', $this);
		parent::__construct();
		if ('TYPO3\Flow\Command\DoctrineCommandController' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {
		if (get_class($this) === 'TYPO3\Flow\Command\DoctrineCommandController') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance('TYPO3\Flow\Command\DoctrineCommandController', $this);

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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Command\DoctrineCommandController');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Command\DoctrineCommandController', $propertyName, 'transient')) continue;
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
		$this->injectSettings(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
		$this->injectReflectionService(\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'));
		$doctrineService_reference = &$this->doctrineService;
		$this->doctrineService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Persistence\Doctrine\Service');
		if ($this->doctrineService === NULL) {
			$this->doctrineService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('89b8d4d3f05317f7b010f4cff94ed417', $doctrineService_reference);
			if ($this->doctrineService === NULL) {
				$this->doctrineService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('89b8d4d3f05317f7b010f4cff94ed417',  $doctrineService_reference, 'TYPO3\Flow\Persistence\Doctrine\Service', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\Doctrine\Service'); });
			}
		}
	}
}
#