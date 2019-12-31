<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\ExtractionInterface;
use Laminas\Hydrator\HydratorPluginManager;

class EntityHydratorManager
{
    /**
     * @var HydratorPluginManager
     */
    protected $hydrators;

    /**
     * @var MetadataMap
     */
    protected $metadataMap;

    /**
     * Map of class name/(hydrator instance|name) pairs
     *
     * @var array
     */
    protected $hydratorMap = [];

    /**
     * Default hydrator to use if no hydrator found for a specific entity class.
     *
     * @var ExtractionInterface
     */
    protected $defaultHydrator;

    /**
     * @param HydratorPluginManager $hydrators
     * @param MetadataMap $map
     */
    public function __construct(HydratorPluginManager $hydrators, MetadataMap $map)
    {
        $this->hydrators   = $hydrators;
        $this->metadataMap = $map;
    }

    /**
     * @return HydratorPluginManager
     */
    public function getHydratorManager()
    {
        return $this->hydrators;
    }

    /**
     * Map an entity class to a specific hydrator instance
     *
     * @param  string $class
     * @param  ExtractionInterface $hydrator
     * @return self
     */
    public function addHydrator($class, $hydrator)
    {
        if (! $hydrator instanceof ExtractionInterface) {
            $hydrator = $this->hydrators->get($hydrator);
        }

        $filteredClass = strtolower($class);
        $this->hydratorMap[$filteredClass] = $hydrator;
        return $this;
    }

    /**
     * Set the default hydrator to use if none specified for a class.
     *
     * @param  ExtractionInterface $hydrator
     * @return self
     */
    public function setDefaultHydrator(ExtractionInterface $hydrator)
    {
        $this->defaultHydrator = $hydrator;
        return $this;
    }

    /**
     * Retrieve a hydrator for a given entity
     *
     * If the entity has a mapped hydrator, returns that hydrator. If not, and
     * a default hydrator is present, the default hydrator is returned.
     * Otherwise, a boolean false is returned.
     *
     * @param  object $entity
     * @return ExtractionInterface|false
     */
    public function getHydratorForEntity($entity)
    {
        $class = get_class($entity);
        $classLower = strtolower($class);

        if (isset($this->hydratorMap[$classLower])) {
            return $this->hydratorMap[$classLower];
        }

        if ($this->metadataMap->has($entity)) {
            $metadata = $this->metadataMap->get($class);
            $hydrator = $metadata->getHydrator();
            if ($hydrator instanceof ExtractionInterface) {
                $this->addHydrator($class, $hydrator);
                return $hydrator;
            }
        }

        if ($this->defaultHydrator instanceof ExtractionInterface) {
            return $this->defaultHydrator;
        }

        return false;
    }
}
