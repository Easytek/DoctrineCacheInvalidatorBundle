<?php

namespace Easytek\DoctrineCacheInvalidatorBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class CacheInvalidator
{
	/**
	 * @var LoggerInterface
	 */
	protected $logger;
    protected $cacheInvalidationServices = array();
    protected $classes = array();

    public function __construct(LoggerInterface $logger)
    {
    	$this->logger = $logger;
    }

    public function addService(CacheInvalidationInterface $cacheInvalidation)
    {
        $this->classes = array_merge($this->classes, $cacheInvalidation->getClasses());
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $scheduledEntityChanges = array(
            'insert' => $uow->getScheduledEntityInsertions(),
            'update' => $uow->getScheduledEntityUpdates(),
            'delete' => $uow->getScheduledEntityDeletions()
        );

        $cacheIds = array();

        foreach ($scheduledEntityChanges as $change => $entities) {
            foreach ($entities as $entity) {
                $entityClass = get_class($entity);

                // If the current entity class has to trigger an invalidation
                if (in_array($entityClass, array_keys($this->classes))) {
                    // For each cache id pattern we have to invalidate for the current change type
                    foreach ($this->getPatternByChange($entityClass, $change) as $pattern) {
                        // We generate the corresponding id and store it for later invalidation
                        $cacheIds[] = $this->generateCacheId($pattern, $entity);
                    }
                }
            }
        }

        $this->clearCache($em, $cacheIds);
    }

    /**
     * We walk through the cache ids and call the result cache implementation delete*() methods to invalidate each stored cache id
     *
     * @param EntityManager $em
     * @param array $cacheIds
     */
    protected function clearCache(EntityManager $em, $cacheIds)
    {
        $cacheIds = array_unique($cacheIds);

        /**
         * @var $resultCache CacheProvider
         */
        $resultCache = $em->getConfiguration()->getResultCacheImpl();

        foreach ($cacheIds as $cacheId) {
            if ($cacheId == '*') {
                $resultCache->deleteAll();
                continue;
            }

            $resultCache->delete($cacheId);

            $this->logger->info('[DoctrineCacheInvalidatorBundle] cache key "' . $cacheId . '" cleared.');
        }
    }

    /**
     * Return an array of cache id patterns for a given class and a given change type (update, insert, delete)
     *
     * @param string $class
     * @param string $change
     * @return array
     */
    protected function getPatternByChange($class, $change)
    {
        $patterns = array();

        if (isset($this->classes[$class])) {
            foreach ($this->classes[$class] as $cacheId) {
                if (in_array('*', $cacheId['changes']) || in_array($change, $cacheId['changes'])) {
                    $patterns[] = $cacheId['pattern'];
                }
            }
        }

        return $patterns;
    }

    protected function generateCacheId($pattern, $entity)
    {
        // Does the pattern contains "variables" parts
        if (preg_match_all('/{([^}]+)}/', $pattern, $match)) {
            $mapping = [];

            foreach ($match[1] as $k => $attributeSpec) {
                $currentEntity = $entity;
                $attributeValue = null;
                $attributeChain = explode('.', $attributeSpec);

                foreach ($attributeChain as $attribute) {
                    $getter = 'get'.ucfirst($attribute);

                    if (!is_object($currentEntity)) {
                        throw new \Exception(
                            sprintf(
                                'The cache id pattern "%s" needs a "%s" value but the value of "%s" is not an object.',
                                $pattern,
                                $attributeSpec,
                                $attribute
                            )
                        );
                    } elseif (!method_exists($currentEntity, $getter)) {
                        throw new \Exception(
                            sprintf(
                                'The cache id pattern "%s" needs a "%s" value but the current "%s" object does not have a "%s" method.',
                                $pattern,
                                $attributeSpec,
                                get_class($currentEntity),
                                $getter
                            )
                        );
                    }

                    $attributeValue = $currentEntity->$getter();
                    $currentEntity = $attributeValue;
                }

                $mapping[$match[0][$k]] = $attributeValue;
            }

            $key = strtr($pattern, $mapping);
        } else {
            $key = $pattern;
        }

        return $key;
    }
}
