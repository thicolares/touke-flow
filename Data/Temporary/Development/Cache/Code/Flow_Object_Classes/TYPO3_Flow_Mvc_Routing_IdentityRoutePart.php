<?php
namespace TYPO3\Flow\Mvc\Routing;

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
 * Identity Route Part
 * This route part can be used to create and resolve ObjectPathMappings.
 * This handler is used by default, if an objectType is specified for a route part in the routing configuration:
 * -
 *   name: 'Some route for xyz entities'
 *   uriPattern: '{xyz}'
 *   routeParts:
 *     xyz:
 *       objectType: Some\Package\Domain\Model\Xyz
 *
 * @see \TYPO3\Flow\Mvc\Routing\ObjectPathMapping
 * @api
 */
class IdentityRoutePart_Original extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository
	 * @Flow\Inject
	 */
	protected $objectPathMappingRepository;

	/**
	 * The object type (class name) of the entity this route part belongs to
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * pattern for the URI representation (for example "{date:Y}/{date:m}/{date.d}/{title}")
	 *
	 * @var string
	 */
	protected $uriPattern = NULL;

	/**
	 * @param string $objectType
	 * @return void
	 */
	public function setObjectType($objectType) {
		$this->objectType = $objectType;
	}

	/**
	 * @return string
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @param string $uriPattern
	 * @return void
	 */
	public function setUriPattern($uriPattern) {
		$this->uriPattern = $uriPattern;
	}

	/**
	 * If $this->uriPattern is specified, this will be returned, otherwise identity properties of $this->objectType
	 * are returned in the format {property1}/{property2}/{property3}.
	 * If $this->objectType does not contain identity properties, an empty string is returned.
	 *
	 * @return string
	 */
	public function getUriPattern() {
		if ($this->uriPattern === NULL) {
			$classSchema = $this->reflectionService->getClassSchema($this->objectType);
			$identityProperties = $classSchema->getIdentityProperties();
			if (count($identityProperties) === 0) {
				$this->uriPattern = '';
			} else {
				$this->uriPattern = '{' . implode('}/{', array_keys($identityProperties)) . '}';
			}
		}
		return $this->uriPattern;
	}

	/**
	 * Checks, whether given value can be matched.
	 * If the value is empty, FALSE is returned.
	 * Otherwise the ObjectPathMappingRepository is asked for a matching ObjectPathMapping.
	 * If that is found the identifier is stored in $this->value, otherwise this route part does not match.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @api
	 * @todo make findOneByObjectTypeUriPatternAndPathSegment case sensitive if lowerCase = FALSE (this is not yet supported by the persistence)
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}
		$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $value);
		if ($objectPathMapping === NULL) {
			return FALSE;
		}
		$this->value = array('__identity' => $objectPathMapping->getIdentifier());
		return TRUE;
	}

	/**
	 * Returns the first part of $routePath that should be evaluated in matchValue().
	 * If not split string is set (route part is the last in the routes uriPattern), the complete $routePart is returned.
	 * Otherwise the part is returned that matches the specified uriPattern of this route part.
	 *
	 * @param string $routePath The request path to be matched
	 * @return string value to match, or an empty string if $routePath is empty, split string was not found or uriPattern could not be matched
	 * @api
	 */
	protected function findValueToMatch($routePath) {
		if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
			return '';
		}
		$uriPattern = $this->getUriPattern();
		if ($uriPattern === '') {
			return '';
		}
		$regexPattern = preg_quote($uriPattern, '/');
		$regexPattern = preg_replace('/\\\\{[^}]+\\\\}/', '[^\/]+', $regexPattern);
		if ($this->splitString !== '') {
			$regexPattern .= '(?=' . preg_quote($this->splitString, '/') . ')';
		}
		$matches = array();
		preg_match('/^' . $regexPattern . '/', trim($routePath, '/'), $matches);
		return isset($matches[0]) ? $matches[0] : '';
	}

	/**
	 * Resolves the given entity and sets the value to a URI representation (path segment) that matches $this->uriPattern and is unique for the given object.
	 *
	 * @param mixed $value
	 * @return boolean TRUE if the object could be resolved and stored in $this->value, otherwise FALSE.
	 * @throws \TYPO3\Flow\Mvc\Exception\InfiniteLoopException if no unique path segment could be found after 100 iterations
	 */
	protected function resolveValue($value) {
		if (is_array($value) && isset($value['__identity'])) {
			$identifier = $value['__identity'];
		} elseif ($value instanceof $this->objectType) {
			$identifier = $this->persistenceManager->getIdentifierByObject($value);
		} else {
			return FALSE;
		}

		$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndIdentifier($this->objectType, $this->getUriPattern(), $identifier);
		if ($objectPathMapping !== NULL) {
			$this->value = $objectPathMapping->getPathSegment();
			return TRUE;
		}
		$pathSegment = $uniquePathSegment = $this->createPathSegmentForObject($value);
		$pathSegmentLoopCount = 0;
		do {
			if ($pathSegmentLoopCount++ > 99) {
				throw new \TYPO3\Flow\Mvc\Exception\InfiniteLoopException('No unique path segment could be found after ' . ($pathSegmentLoopCount - 1) . ' iterations.', 1316441798);
			}
			if ($uniquePathSegment !== '') {
				$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $uniquePathSegment);
				if ($objectPathMapping === NULL) {
					$this->storeObjectPathMapping($uniquePathSegment, $identifier);
					break;
				}
			}
			$uniquePathSegment = sprintf('%s-%d', $pathSegment, $pathSegmentLoopCount);
		} while (TRUE);

		$this->value = $uniquePathSegment;
		return TRUE;
	}

	/**
	 * Creates a URI representation (path segment) for the given object matching $this->uriPattern.
	 *
	 * @param mixed $object object of type $this->objectType
	 * @return string URI representation (path segment) of the given object
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
	 */
	protected function createPathSegmentForObject($object) {
		$uriPattern = $this->getUriPattern();
		if ($uriPattern === '') {
			return $this->rewriteForUri($this->persistenceManager->getIdentifierByObject($object));
		}
		$matches = array();
		preg_match_all('/(?P<dynamic>{?)(?P<content>[^}{]+)}?/', $uriPattern, $matches, PREG_SET_ORDER);
		$pathSegment = '';
		foreach ($matches as $match) {
			if (empty($match['dynamic'])) {
				$pathSegment .= $match['content'];
			} else {
				$dynamicPathSegmentParts = explode(':', $match['content']);
				$propertyPath = $dynamicPathSegmentParts[0];
				$dynamicPathSegment = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($object, $propertyPath);
				if (is_object($dynamicPathSegment)) {
					if ($dynamicPathSegment instanceof \DateTime) {
						$dateFormat = isset($dynamicPathSegmentParts[1]) ? trim($dynamicPathSegmentParts[1]) : 'Y-m-d';
						$pathSegment .= $this->rewriteForUri($dynamicPathSegment->format($dateFormat));
					} else {
						throw new \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException('Invalid uriPattern "' . $uriPattern . '" for route part "' . $this->getName() . '". Property "' . $propertyPath . '" must be of type string or \DateTime. "' . (is_object($dynamicPathSegment) ? get_class($dynamicPathSegment) : gettype($dynamicPathSegment)) . '" given.', 1316442409);
					}
				} else {
					$pathSegment .= $this->rewriteForUri($dynamicPathSegment);
				}
			}
		}
		return $pathSegment;
	}

	/**
	 * Creates a new ObjectPathMapping and stores it in the repository
	 *
	 * @param string $pathSegment
	 * @param mixed $identifier
	 * @return void
	 */
	protected function storeObjectPathMapping($pathSegment, $identifier) {
		$objectPathMapping = new \TYPO3\Flow\Mvc\Routing\ObjectPathMapping();
		$objectPathMapping->setObjectType($this->objectType);
		$objectPathMapping->setUriPattern($this->getUriPattern());
		$objectPathMapping->setPathSegment($pathSegment);
		$objectPathMapping->setIdentifier($identifier);
		$this->objectPathMappingRepository->add($objectPathMapping);
		// TODO can be removed, when persistence manager has some memory cache
		$this->persistenceManager->persistAll();
	}

	/**
	 * Transforms the given string into a URI compatible format without special characters.
	 * In the long term this should be done with proper transliteration
	 *
	 * @param string $value
	 * @return string
	 * @todo use transliteration of the I18n sub package
	 */
	protected function rewriteForUri($value) {
		$transliteration = array(
			'ä' => 'ae',
			'Ä' => 'Ae',
			'ö' => 'oe',
			'Ö' => 'Oe',
			'ü' => 'ue',
			'Ü' => 'Ue',
			'ß' => 'ss',
		);
		$value = strtr($value, $transliteration);

		$spaceCharacter = '-';
		$value = preg_replace('/[ \-+_]+/', $spaceCharacter, $value);

		$value = preg_replace('/[^-a-z0-9.\\' . $spaceCharacter . ']/i', '', $value);

		$value = preg_replace('/\\' . $spaceCharacter . '{2,}/', $spaceCharacter, $value);
		$value = trim($value, $spaceCharacter);

		return $value;
	}


}
namespace TYPO3\Flow\Mvc\Routing;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Identity Route Part
 * This route part can be used to create and resolve ObjectPathMappings.
 * This handler is used by default, if an objectType is specified for a route part in the routing configuration:
 * -
 *   name: 'Some route for xyz entities'
 *   uriPattern: '{xyz}'
 *   routeParts:
 *     xyz:
 *       objectType: Some\Package\Domain\Model\Xyz
 */
class IdentityRoutePart extends IdentityRoutePart_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	public function __construct() {
		if ('TYPO3\Flow\Mvc\Routing\IdentityRoutePart' === get_class($this)) {
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
	$reflectedClass = new \ReflectionClass('TYPO3\Flow\Mvc\Routing\IdentityRoutePart');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('TYPO3\Flow\Mvc\Routing\IdentityRoutePart', $propertyName, 'transient')) continue;
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
		$persistenceManager_reference = &$this->persistenceManager;
		$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		if ($this->persistenceManager === NULL) {
			$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('f1bc82ad47156d95485678e33f27c110', $persistenceManager_reference);
			if ($this->persistenceManager === NULL) {
				$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('f1bc82ad47156d95485678e33f27c110',  $persistenceManager_reference, 'TYPO3\Flow\Persistence\Doctrine\PersistenceManager', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'); });
			}
		}
		$reflectionService_reference = &$this->reflectionService;
		$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Reflection\ReflectionService');
		if ($this->reflectionService === NULL) {
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('921ad637f16d2059757a908fceaf7076', $reflectionService_reference);
			if ($this->reflectionService === NULL) {
				$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('921ad637f16d2059757a908fceaf7076',  $reflectionService_reference, 'TYPO3\Flow\Reflection\ReflectionService', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService'); });
			}
		}
		$objectPathMappingRepository_reference = &$this->objectPathMappingRepository;
		$this->objectPathMappingRepository = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getInstance('TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository');
		if ($this->objectPathMappingRepository === NULL) {
			$this->objectPathMappingRepository = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash('3862e781bdaf94567bbf06c730fb9df7', $objectPathMappingRepository_reference);
			if ($this->objectPathMappingRepository === NULL) {
				$this->objectPathMappingRepository = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency('3862e781bdaf94567bbf06c730fb9df7',  $objectPathMappingRepository_reference, 'TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository', function() { return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository'); });
			}
		}
	}
}
#