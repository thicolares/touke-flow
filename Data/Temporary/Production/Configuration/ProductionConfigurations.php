<?php return array (
  'Settings' => 
  array (
    'TYPO3' => 
    array (
      'Flow' => 
      array (
        'aop' => 
        array (
          'globalObjects' => 
          array (
            'securityContext' => 'TYPO3\\Flow\\Security\\Context',
          ),
        ),
        'configuration' => 
        array (
          'compileConfigurationFiles' => true,
        ),
        'core' => 
        array (
          'context' => 'Production',
          'phpBinaryPathAndFilename' => '/opt/local/bin/php',
          'subRequestEnvironmentVariables' => 
          array (
          ),
          'subRequestPhpIniPathAndFilename' => NULL,
        ),
        'error' => 
        array (
          'exceptionHandler' => 
          array (
            'className' => 'TYPO3\\Flow\\Error\\ProductionExceptionHandler',
            'defaultRenderingOptions' => 
            array (
              'renderTechnicalDetails' => false,
            ),
            'renderingGroups' => 
            array (
              'notFoundExceptions' => 
              array (
                'matchingStatusCodes' => 
                array (
                  0 => 404,
                ),
                'options' => 
                array (
                  'templatePathAndFilename' => 'resource://TYPO3.Flow/Private/Templates/Error/Default.html',
                  'variables' => 
                  array (
                    'errorDescription' => 'Sorry, the page you requested was not found.',
                  ),
                ),
              ),
              'databaseConnectionExceptions' => 
              array (
                'matchingExceptionClassNames' => 
                array (
                  0 => 'TYPO3\\Flow\\Persistence\\Doctrine\\DatabaseConnectionException',
                ),
                'options' => 
                array (
                  'templatePathAndFilename' => 'resource://TYPO3.Flow/Private/Templates/Error/Default.html',
                  'variables' => 
                  array (
                    'errorDescription' => 'Sorry, the database connection couldn\'t be established.',
                  ),
                ),
              ),
            ),
          ),
          'errorHandler' => 
          array (
            'exceptionalErrors' => 
            array (
              0 => 256,
              1 => 4096,
            ),
          ),
        ),
        'http' => 
        array (
          'baseUri' => NULL,
        ),
        'log' => 
        array (
          'systemLogger' => 
          array (
            'logger' => 'TYPO3\\Flow\\Log\\Logger',
            'backend' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
            'backendOptions' => 
            array (
              'logFileURL' => '/Library/WebServer/Documents/touke-flow/Data/Logs/System.log',
              'createParentDirectories' => true,
              'severityThreshold' => 6,
              'maximumLogFileSize' => 10485760,
              'logFilesToKeep' => 1,
              'logMessageOrigin' => false,
            ),
          ),
          'securityLogger' => 
          array (
            'backend' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
            'backendOptions' => 
            array (
              'logFileURL' => '/Library/WebServer/Documents/touke-flow/Data/Logs/Security.log',
              'createParentDirectories' => true,
              'severityThreshold' => 6,
              'maximumLogFileSize' => 10485760,
              'logFilesToKeep' => 1,
              'logIpAddress' => true,
            ),
          ),
          'sqlLogger' => 
          array (
            'backend' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
            'backendOptions' => 
            array (
              'logFileURL' => '/Library/WebServer/Documents/touke-flow/Data/Logs/Query.log',
              'createParentDirectories' => true,
              'severityThreshold' => 6,
              'maximumLogFileSize' => 10485760,
              'logFilesToKeep' => 1,
            ),
          ),
        ),
        'i18n' => 
        array (
          'defaultLocale' => 'en',
          'fallbackRule' => 
          array (
            'strict' => false,
            'order' => 
            array (
            ),
          ),
        ),
        'object' => 
        array (
          'registerFunctionalTestClasses' => false,
          'excludeClasses' => 
          array (
            'Doctrine.*' => 
            array (
              0 => '.*',
            ),
            'doctrine.*' => 
            array (
              0 => '.*',
            ),
            'symfony.*' => 
            array (
              0 => '.*',
            ),
            'phpunit.*' => 
            array (
              0 => '.*',
            ),
            'mikey179.vfsStream' => 
            array (
              0 => '.*',
            ),
            'Composer.Installers' => 
            array (
              0 => '.*',
            ),
          ),
        ),
        'package' => 
        array (
          'inactiveByDefault' => 
          array (
            0 => 'Composer.Installers',
          ),
        ),
        'persistence' => 
        array (
          'backendOptions' => 
          array (
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => NULL,
            'user' => NULL,
            'password' => NULL,
            'charset' => 'utf8',
          ),
          'doctrine' => 
          array (
            'enable' => true,
            'sqlLogger' => NULL,
          ),
        ),
        'reflection' => 
        array (
          'ignoredTags' => 
          array (
            0 => 'api',
            1 => 'package',
            2 => 'subpackage',
            3 => 'license',
            4 => 'copyright',
            5 => 'author',
            6 => 'const',
            7 => 'see',
            8 => 'todo',
            9 => 'scope',
            10 => 'fixme',
            11 => 'test',
            12 => 'expectedException',
            13 => 'depends',
            14 => 'dataProvider',
            15 => 'group',
            16 => 'codeCoverageIgnore',
          ),
          'logIncorrectDocCommentHints' => false,
        ),
        'resource' => 
        array (
          'publishing' => 
          array (
            'detectPackageResourceChanges' => false,
            'fileSystem' => 
            array (
              'mirrorMode' => 'link',
            ),
          ),
        ),
        'security' => 
        array (
          'enable' => true,
          'firewall' => 
          array (
            'rejectAll' => false,
            'filters' => 
            array (
              0 => 
              array (
                'patternType' => 'CsrfProtection',
                'patternValue' => NULL,
                'interceptor' => 'AccessDeny',
              ),
            ),
          ),
          'authentication' => 
          array (
            'providers' => 
            array (
            ),
            'authenticationStrategy' => 'atLeastOneToken',
          ),
          'authorization' => 
          array (
            'accessDecisionVoters' => 
            array (
              0 => 'TYPO3\\Flow\\Security\\Authorization\\Voter\\Policy',
            ),
            'allowAccessIfAllVotersAbstain' => false,
          ),
          'csrf' => 
          array (
            'csrfStrategy' => 'onePerSession',
          ),
          'cryptography' => 
          array (
            'hashingStrategies' => 
            array (
              'default' => 'bcrypt',
              'fallback' => 'pbkdf2',
              'pbkdf2' => 'TYPO3\\Flow\\Security\\Cryptography\\Pbkdf2HashingStrategy',
              'bcrypt' => 'TYPO3\\Flow\\Security\\Cryptography\\BCryptHashingStrategy',
              'saltedmd5' => 'TYPO3\\Flow\\Security\\Cryptography\\SaltedMd5HashingStrategy',
            ),
            'Pbkdf2HashingStrategy' => 
            array (
              'dynamicSaltLength' => 8,
              'iterationCount' => 10000,
              'derivedKeyLength' => 64,
              'algorithm' => 'sha256',
            ),
            'BCryptHashingStrategy' => 
            array (
              'cost' => 14,
            ),
            'RSAWalletServicePHP' => 
            array (
              'keystorePath' => '/Library/WebServer/Documents/touke-flow/Data/Persistent/RsaWalletData',
              'openSSLConfiguration' => 
              array (
              ),
            ),
          ),
        ),
        'session' => 
        array (
          'inactivityTimeout' => 3600,
          'name' => 'TYPO3_Flow_Session',
          'garbageCollection' => 
          array (
            'probability' => 30,
            'maximumPerRun' => 1000,
          ),
          'cookie' => 
          array (
            'lifetime' => 0,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'domain' => NULL,
          ),
        ),
        'utility' => 
        array (
          'environment' => 
          array (
            'temporaryDirectoryBase' => '/Library/WebServer/Documents/touke-flow/Data/Temporary/',
          ),
        ),
      ),
      'Fluid' => 
      array (
      ),
      'Party' => 
      array (
      ),
      'Kickstart' => 
      array (
      ),
      'Surf' => 
      array (
      ),
      'DocTools' => 
      array (
        'bundles' => 
        array (
          'TYPO3SurfHtml' => 
          array (
            'documentationRootPath' => '/Library/WebServer/Documents/touke-flow/Packages/Application/TYPO3.Surf/Documentation/Guide/',
            'configurationRootPath' => '/Library/WebServer/Documents/touke-flow/Packages/Documentation/TYPO3.DocTools/Resources/Private/Themes/FLOW3/',
            'renderedDocumentationRootPath' => '/Library/WebServer/Documents/touke-flow/Data/Temporary/Documentation/TYPO3.Surf/',
            'renderingOutputFormat' => 'html',
          ),
        ),
      ),
    ),
    'Composer' => 
    array (
      'Installers' => 
      array (
      ),
    ),
    'Doctrine' => 
    array (
      'Common' => 
      array (
      ),
      'DBAL' => 
      array (
      ),
      'ORM' => 
      array (
      ),
    ),
    'symfony' => 
    array (
      'console' => 
      array (
      ),
      'domcrawler' => 
      array (
      ),
      'yaml' => 
      array (
      ),
    ),
    'doctrine' => 
    array (
      'migrations' => 
      array (
      ),
    ),
    'mikey179' => 
    array (
      'vfsStream' => 
      array (
      ),
    ),
    'Apimenti' => 
    array (
      'Translator' => 
      array (
      ),
    ),
  ),
  'Caches' => 
  array (
    'Default' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\VariableFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\FileBackend',
      'backendOptions' => 
      array (
        'defaultLifetime' => 0,
      ),
    ),
    'Flow_Cache_ResourceFiles' => 
    array (
    ),
    'Flow_Core' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\StringFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_I18n_AvailableLocalesCache' => 
    array (
    ),
    'Flow_I18n_XmlModelCache' => 
    array (
    ),
    'Flow_I18n_Cldr_CldrModelCache' => 
    array (
    ),
    'Flow_I18n_Cldr_Reader_DatesReaderCache' => 
    array (
    ),
    'Flow_I18n_Cldr_Reader_NumbersReaderCache' => 
    array (
    ),
    'Flow_I18n_Cldr_Reader_PluralsReaderCache' => 
    array (
    ),
    'Flow_Monitor' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Mvc_Routing_FindMatchResults' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Mvc_Routing_Resolve' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\StringFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Object_Classes' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\PhpFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Object_Configuration' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Persistence_Doctrine' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Reflection_Status' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\StringFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Reflection_CompiletimeData' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\SimpleFileBackend',
    ),
    'Flow_Reflection_RuntimeData' => 
    array (
    ),
    'Flow_Reflection_RuntimeClassSchemata' => 
    array (
    ),
    'Flow_Resource_Status' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\StringFrontend',
    ),
    'Flow_Security_Policy' => 
    array (
    ),
    'Flow_Security_Cryptography_RSAWallet' => 
    array (
      'backendOptions' => 
      array (
        'defaultLifetime' => 30,
      ),
    ),
    'Flow_Session_MetaData' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\FileBackend',
    ),
    'Flow_Session_Storage' => 
    array (
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\FileBackend',
    ),
    'Fluid_TemplateCache' => 
    array (
      'frontend' => 'TYPO3\\Flow\\Cache\\Frontend\\PhpFrontend',
      'backend' => 'TYPO3\\Flow\\Cache\\Backend\\FileBackend',
    ),
  ),
  'Objects' => 
  array (
    'Composer.Installers' => 
    array (
    ),
    'Doctrine.Common' => 
    array (
    ),
    'Doctrine.DBAL' => 
    array (
    ),
    'symfony.console' => 
    array (
    ),
    'Doctrine.ORM' => 
    array (
    ),
    'doctrine.migrations' => 
    array (
    ),
    'mikey179.vfsStream' => 
    array (
    ),
    'symfony.domcrawler' => 
    array (
    ),
    'symfony.yaml' => 
    array (
    ),
    'TYPO3.Flow' => 
    array (
      'DateTime' => 
      array (
        'scope' => 'prototype',
        'autowiring' => 'off',
      ),
      'TYPO3\\Flow\\Cache\\CacheFactory' => 
      array (
        'arguments' => 
        array (
          1 => 
          array (
            'setting' => 'TYPO3.Flow.context',
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Service' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_AvailableLocalesCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Cldr\\CldrModel' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_Cldr_CldrModelCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Xliff\\XliffModel' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_XmlModelCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Cldr\\Reader\\DatesReader' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_Cldr_Reader_DatesReaderCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Cldr\\Reader\\NumbersReader' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_Cldr_Reader_NumbersReaderCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\I18n\\Cldr\\Reader\\PluralsReader' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_I18n_Cldr_Reader_PluralsReaderCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Log\\Backend\\FileBackend' => 
      array (
        'autowiring' => 'off',
      ),
      'TYPO3\\Flow\\Log\\Backend\\NullBackend' => 
      array (
        'autowiring' => 'off',
      ),
      'TYPO3\\Flow\\Log\\SystemLoggerInterface' => 
      array (
        'scope' => 'singleton',
        'factoryObjectName' => 'TYPO3\\Flow\\Log\\LoggerFactory',
        'arguments' => 
        array (
          1 => 
          array (
            'value' => 'Flow_System',
          ),
          2 => 
          array (
            'value' => 'TYPO3\\Flow\\Log\\Logger',
          ),
          3 => 
          array (
            'value' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
          ),
          4 => 
          array (
            'setting' => 'TYPO3.Flow.log.systemLogger.backendOptions',
          ),
        ),
      ),
      'TYPO3\\Flow\\Log\\SecurityLoggerInterface' => 
      array (
        'scope' => 'singleton',
        'factoryObjectName' => 'TYPO3\\Flow\\Log\\LoggerFactory',
        'arguments' => 
        array (
          1 => 
          array (
            'value' => 'Flow_Security',
          ),
          2 => 
          array (
            'value' => 'TYPO3\\Flow\\Log\\Logger',
          ),
          3 => 
          array (
            'value' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
          ),
          4 => 
          array (
            'setting' => 'TYPO3.Flow.log.securityLogger.backendOptions',
          ),
        ),
      ),
      'TYPO3\\Flow\\Monitor\\ChangeDetectionStrategy\\ModificationTimeStrategy' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Monitor',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Monitor\\FileMonitor' => 
      array (
        'properties' => 
        array (
          'cache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Monitor',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Mvc\\Routing\\Aspect\\RouterCachingAspect' => 
      array (
        'properties' => 
        array (
          'findMatchResultsCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Mvc_Routing_FindMatchResults',
                ),
              ),
            ),
          ),
          'resolveCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Mvc_Routing_Resolve',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Object\\ObjectManagerInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Object\\ObjectManager',
        'scope' => 'singleton',
        'autowiring' => 'off',
      ),
      'TYPO3\\Flow\\Object\\ObjectManager' => 
      array (
        'autowiring' => 'off',
      ),
      'TYPO3\\Flow\\Object\\CompileTimeObjectManager' => 
      array (
        'autowiring' => 'off',
      ),
      'Doctrine\\Common\\Persistence\\ObjectManager' => 
      array (
        'scope' => 'singleton',
        'factoryObjectName' => 'TYPO3\\Flow\\Persistence\\Doctrine\\EntityManagerFactory',
      ),
      'TYPO3\\Flow\\Persistence\\PersistenceManagerInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Persistence\\Doctrine\\PersistenceManager',
      ),
      'TYPO3\\Flow\\Persistence\\Doctrine\\Logging\\SqlLogger' => 
      array (
        'properties' => 
        array (
          'logger' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Log\\LoggerFactory',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Sql_Queries',
                ),
                2 => 
                array (
                  'value' => 'TYPO3\\Flow\\Log\\Logger',
                ),
                3 => 
                array (
                  'value' => 'TYPO3\\Flow\\Log\\Backend\\FileBackend',
                ),
                4 => 
                array (
                  'setting' => 'TYPO3.Flow.log.sqlLogger.backendOptions',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Resource\\ResourceManager' => 
      array (
        'properties' => 
        array (
          'statusCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Resource_Status',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Resource\\Publishing\\ResourcePublishingTargetInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Resource\\Publishing\\FileSystemPublishingTarget',
      ),
      'TYPO3\\Flow\\Security\\Authentication\\AuthenticationManagerInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Security\\Authentication\\AuthenticationProviderManager',
      ),
      'TYPO3\\Flow\\Security\\Cryptography\\RsaWalletServiceInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Security\\Cryptography\\RsaWalletServicePhp',
        'scope' => 'singleton',
        'properties' => 
        array (
          'keystoreCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Security_Cryptography_RSAWallet',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Security\\Authorization\\AccessDecisionManagerInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Security\\Authorization\\AccessDecisionVoterManager',
      ),
      'TYPO3\\Flow\\Security\\Authorization\\FirewallInterface' => 
      array (
        'className' => 'TYPO3\\Flow\\Security\\Authorization\\FilterFirewall',
      ),
      'TYPO3\\Flow\\Security\\Cryptography\\Pbkdf2HashingStrategy' => 
      array (
        'scope' => 'singleton',
        'arguments' => 
        array (
          1 => 
          array (
            'setting' => 'TYPO3.Flow.security.cryptography.Pbkdf2HashingStrategy.dynamicSaltLength',
          ),
          2 => 
          array (
            'setting' => 'TYPO3.Flow.security.cryptography.Pbkdf2HashingStrategy.iterationCount',
          ),
          3 => 
          array (
            'setting' => 'TYPO3.Flow.security.cryptography.Pbkdf2HashingStrategy.derivedKeyLength',
          ),
          4 => 
          array (
            'setting' => 'TYPO3.Flow.security.cryptography.Pbkdf2HashingStrategy.algorithm',
          ),
        ),
      ),
      'TYPO3\\Flow\\Security\\Cryptography\\BCryptHashingStrategy' => 
      array (
        'scope' => 'singleton',
        'arguments' => 
        array (
          1 => 
          array (
            'setting' => 'TYPO3.Flow.security.cryptography.BCryptHashingStrategy.cost',
          ),
        ),
      ),
      'TYPO3\\Flow\\Session\\SessionInterface' => 
      array (
        'scope' => 'singleton',
        'factoryObjectName' => 'TYPO3\\Flow\\Session\\SessionManager',
        'factoryMethodName' => 'getCurrentSession',
      ),
      'TYPO3\\Flow\\Session\\Session' => 
      array (
        'properties' => 
        array (
          'metaDataCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Session_MetaData',
                ),
              ),
            ),
          ),
          'storageCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Session_Storage',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Session\\SessionManager' => 
      array (
        'properties' => 
        array (
          'metaDataCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Flow_Session_MetaData',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Flow\\Utility\\PdoHelper' => 
      array (
        'autowiring' => 'off',
        'scope' => 'prototype',
      ),
    ),
    'TYPO3.Fluid' => 
    array (
      'TYPO3\\Fluid\\Core\\Compiler\\TemplateCompiler' => 
      array (
        'properties' => 
        array (
          'templateCache' => 
          array (
            'object' => 
            array (
              'factoryObjectName' => 'TYPO3\\Flow\\Cache\\CacheManager',
              'factoryMethodName' => 'getCache',
              'arguments' => 
              array (
                1 => 
                array (
                  'value' => 'Fluid_TemplateCache',
                ),
              ),
            ),
          ),
        ),
      ),
      'TYPO3\\Fluid\\View\\TemplateView' => 
      array (
        'properties' => 
        array (
          'renderingContext' => 
          array (
            'object' => 'TYPO3\\Fluid\\Core\\Rendering\\RenderingContext',
          ),
        ),
      ),
      'TYPO3\\Fluid\\View\\StandaloneView' => 
      array (
        'properties' => 
        array (
          'renderingContext' => 
          array (
            'object' => 'TYPO3\\Fluid\\Core\\Rendering\\RenderingContext',
          ),
        ),
      ),
    ),
    'TYPO3.Party' => 
    array (
    ),
    'Apimenti.Translator' => 
    array (
    ),
    'TYPO3.Kickstart' => 
    array (
    ),
    'TYPO3.Surf' => 
    array (
    ),
  ),
  'Policy' => 
  array (
    'resources' => 
    array (
      'entities' => 
      array (
      ),
      'methods' => 
      array (
      ),
    ),
    'roles' => 
    array (
    ),
    'acls' => 
    array (
    ),
  ),
)?>