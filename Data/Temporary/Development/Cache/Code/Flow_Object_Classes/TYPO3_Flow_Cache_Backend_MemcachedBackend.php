<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A caching backend which stores cache entries by using Memcached.
 *
 * This backend uses the following types of Memcache keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "Flow"
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * Note: When using the Memcached backend to store values of more than ~1 MB, the
 * data will be split into chunks to make them fit into the memcached limits.
 *
 * @api
 */
class MemcachedBackend_Original extends AbstractBackend implements TaggableBackendInterface {

	/**
	 * Max bucket size, (1024*1024)-42 bytes
	 * @var int
	 */
	const MAX_BUCKET_SIZE = 1048534;

	/**
	 * Instance of the PHP Memcache class
	 *
	 * @var \Memcache
	 */
	protected $memcache;

	/**
	 * Array of Memcache server configurations
	 *
	 * @var array
	 */
	protected $servers = array();

	/**
	 * Indicates whether the memcache uses compression or not (requires zlib),
	 * either 0 or MEMCACHE_COMPRESSED
	 *
	 * @var int
	 */
	protected $flags;

	/**
	 * A prefix to separate stored data from other data possible stored in the memcache
	 *
	 * @var string
	 */
	protected $identifierPrefix;

	/**
	 * Constructs this backend
	 *
	 * @param \TYPO3\Flow\Core\ApplicationContext $context Flow's application context
	 * @param array $options Configuration options - depends on the actual backend
	 * @throws \TYPO3\Flow\Cache\Exception
	 */
	public function __construct(\TYPO3\Flow\Core\ApplicationContext $context, array $options = array()) {
		if (!extension_loaded('memcache')) throw new \TYPO3\Flow\Cache\Exception('The PHP extension "memcache" must be installed and loaded in order to use the Memcached backend.', 1213987706);
		parent::__construct($context, $options);
	}

	/**
	 * Setter for servers to be used. Expects an array,  the values are expected
	 * to be formatted like "<host>[:<port>]" or "unix://<path>"
	 *
	 * @param array $servers An array of servers to add.
	 * @return void
	 * @api
	 */
	protected function setServers(array $servers) {
		$this->servers = $servers;
	}

	/**
	 * Setter for compression flags bit
	 *
	 * @param boolean $useCompression
	 * @return void
	 * @api
	 */
	protected function setCompression($useCompression) {
		if ($useCompression === TRUE) {
			$this->flags ^= MEMCACHE_COMPRESSED;
		} else {
			$this->flags &= ~MEMCACHE_COMPRESSED;
		}
	}

	/**
	 * Initializes the identifier prefix
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Cache\Exception
	 */
	public function initializeObject() {
		if (!count($this->servers)) throw new \TYPO3\Flow\Cache\Exception('No servers were given to Memcache', 1213115903);

		$this->memcache = new \Memcache();
		$defaultPort = ini_get('memcache.default_port');

		foreach ($this->servers as $server) {
			if (substr($server, 0, 7) === 'unix://') {
				$host = $server;
				$port = 0;
			} else {
				if (substr($server, 0, 6) === 'tcp://') {
					$server = substr($server, 6);
				}
				if (strpos($server, ':') !== FALSE) {
					list($host, $port) = explode(':', $server, 2);
				} else {
					$host = $server;
					$port = $defaultPort;
				}
			}
			$this->memcache->addServer($host, $port);
		}
	}

	/**
	 * Initializes the identifier prefix when setting the cache.
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\FrontendInterface $cache
	 * @return void
	 */
	public function setCache(\TYPO3\Flow\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);
		$this->identifierPrefix = 'Flow_' . md5($cache->getIdentifier() . \TYPO3\Flow\Utility\Files::getUnixStylePath($_SERVER['SCRIPT_FILENAME']) . PHP_SAPI) . '_';
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \TYPO3\Flow\Cache\Exception if no cache frontend has been set.
	 * @throws \InvalidArgumentException if the identifier is not valid or the final memcached key is longer than 250 characters
	 * @throws \TYPO3\Flow\Cache\Exception\InvalidDataException if $data is not a string
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (strlen($this->identifierPrefix . $entryIdentifier) > 250) throw new \InvalidArgumentException('Could not set value. Key more than 250 characters (' . $this->identifierPrefix . $entryIdentifier . ').', 1232969508);
		if (!$this->cache instanceof \TYPO3\Flow\Cache\Frontend\FrontendInterface) throw new \TYPO3\Flow\Cache\Exception('No cache frontend has been set yet via setCache().', 1207149215);
		if (!is_string($data)) throw new \TYPO3\Flow\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);

		$tags[] = '%MEMCACHEBE%' . $this->cacheIdentifier;
		$expiration = $lifetime !== NULL ? $lifetime : $this->defaultLifetime;
			// Memcached consideres values over 2592000 sec (30 days) as UNIX timestamp
			// thus $expiration should be converted from lifetime to UNIX timestamp
		if ($expiration > 2592000) {
			$expiration += time();
		}

		try {
			if (strlen($data) > self::MAX_BUCKET_SIZE) {
				$data = str_split($data, self::MAX_BUCKET_SIZE - 1024);
				$success = TRUE;
				$chunkNumber = 1;
				foreach ($data as $chunk) {
					$success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber, $chunk, $this->flags, $expiration);
					$chunkNumber++;
				}
				$success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier, 'Flow*chunked:' . $chunkNumber, $this->flags, $expiration);
			} else {
				$success = $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $this->flags, $expiration);
			}
			if ($success === TRUE) {
				$this->removeIdentifierFromAllTags($entryIdentifier);
				$this->addIdentifierToTags($entryIdentifier, $tags);
			} else {
				throw new \TYPO3\Flow\Cache\Exception('Could not set value on memcache server.', 1275830266);
			}
		} catch (\Exception $exception) {
			throw new \TYPO3\Flow\Cache\Exception('Could not set value. ' . $exception->getMessage(), 1207208100);
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @api
	 */
	public function get($entryIdentifier) {
		$value = $this->memcache->get($this->identifierPrefix . $entryIdentifier);
		if (substr($value, 0, 13) === 'Flow*chunked:') {
			list( , $chunkCount) = explode(':', $value);
			$value = '';
			for ($chunkNumber = 1 ; $chunkNumber < $chunkCount; $chunkNumber++) {
				$value .= $this->memcache->get($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber);
			}
		}
		return $value;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier) {
		return $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== FALSE;
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @api
	 */
	public function remove($entryIdentifier) {
		$this->removeIdentifierFromAllTags($entryIdentifier);
		return $this->memcache->delete($this->identifierPrefix . $entryIdentifier, 0);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		$identifiers = $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
		if ($identifiers !== FALSE) {
			return (array) $identifiers;
		} else {
			return array();
		}
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param string $identifier Identifier to find tags by
	 * @return array Array with tags
	 */
	protected function findTagsByIdentifier($identifier) {
		$tags = $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
		return ($tags === FALSE ? array() : (array)$tags);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Cache\Exception
	 * @api
	 */
	public function flush() {
		if (!$this->cache instanceof \TYPO3\Flow\Cache\Frontend\FrontendInterface) throw new \TYPO3\Flow\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$this->flushByTag('%MEMCACHEBE%' . $this->cacheIdentifier);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$this->remove($identifier);
		}
	}

	/**
	 * Associates the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @return void
	 */
	protected function addIdentifierToTags($entryIdentifier, array $tags) {
		foreach ($tags as $tag) {
				// Update tag-to-identifier index
			$identifiers = $this->findIdentifiersByTag($tag);
			if (array_search($entryIdentifier, $identifiers) === FALSE) {
				$identifiers[] = $entryIdentifier;
				$this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
			}

				// Update identifier-to-tag index
			$existingTags = $this->findTagsByIdentifier($entryIdentifier);
			if (array_search($tag, $existingTags) === FALSE) {
				$this->memcache->set($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
			}
		}
	}

	/**
	 * Removes association of the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @return void
	 */
	protected function removeIdentifierFromAllTags($entryIdentifier) {
			// Get tags for this identifier
		$tags = $this->findTagsByIdentifier($entryIdentifier);
			// Deassociate tags with this identifier
		foreach ($tags as $tag) {
			$identifiers = $this->findIdentifiersByTag($tag);
				// Formally array_search() below should never return false due to
				// the behavior of findTagsByIdentifier(). But if reverse index is
				// corrupted, we still can get 'false' from array_search(). This is
				// not a problem because we are removing this identifier from
				// anywhere.
			if (($key = array_search($entryIdentifier, $identifiers)) !== FALSE) {
				unset($identifiers[$key]);
				if (count($identifiers)) {
					$this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
				} else {
					$this->memcache->delete($this->identifierPrefix . 'tag_' . $tag, 0);
				}
			}
		}
			// Clear reverse tag index for this identifier
		$this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier, 0);
	}

	/**
	 * Does nothing, as memcached does GC itself
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
	}

}

namespace TYPO3\Flow\Cache\Backend;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A caching backend which stores cache entries by using Memcached.
 * 
 * This backend uses the following types of Memcache keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 * 
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "Flow"
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 * 
 * Note: When using the Memcached backend to store values of more than ~1 MB, the
 * data will be split into chunks to make them fit into the memcached limits.
 */
class MemcachedBackend extends MemcachedBackend_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 * @param \TYPO3\Flow\Core\ApplicationContext $context Flow's application context
	 * @param array $options Configuration options - depends on the actual backend
	 * @throws \TYPO3\Flow\Cache\Exception
	 */
	public function __construct() {
		$arguments = func_get_args();

		if (!array_key_exists(0, $arguments)) $arguments[0] = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\ApplicationContext');
		if (!array_key_exists(1, $arguments)) $arguments[1] = array (
);
		if (!array_key_exists(0, $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException('Missing required constructor argument $context in class ' . __CLASS__ . '. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.', 1296143788);
		call_user_func_array('parent::__construct', $arguments);
		if ('TYPO3\Flow\Cache\Backend\MemcachedBackend' === get_class($this)) {
			$this->Flow_Proxy_injectProperties();
		}

		$this->initializeObject(1);
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
		$result = NULL;

		$this->initializeObject(2);
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Cache\Backend\MemcachedBackend');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Cache\Backend\MemcachedBackend', $propertyName, 'transient')) continue;
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
	}
}
#