<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal;

use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\ExtractionInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\HydratorPluginManagerInterface;

use function is_string;
use function sprintf;
use function strtolower;

class EntityHydratorManager
{
    /** @var HydratorPluginManager|HydratorPluginManagerInterface */
    protected $hydrators;

    /** @var MetadataMap */
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
     * @param HydratorPluginManager|HydratorPluginManagerInterface $hydrators
     * @throws Exception\InvalidArgumentException If $hydrators is of invalid type.
     */
    public function __construct($hydrators, MetadataMap $map)
    {
        if ($hydrators instanceof HydratorPluginManagerInterface) {
            $this->hydrators = $hydrators;
        } elseif ($hydrators instanceof HydratorPluginManager) {
            $this->hydrators = $hydrators;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '$hydrators argument to %s must be an instance of either %s or %s; received %s',
                self::class,
                HydratorPluginManagerInterface::class,
                HydratorPluginManager::class,
                $hydrators::class
            ));
        }
        $this->hydrators   = $hydrators;
        $this->metadataMap = $map;
    }

    /**
     * @return HydratorPluginManager|HydratorPluginManagerInterface
     */
    public function getHydratorManager()
    {
        return $this->hydrators;
    }

    /**
     * Map an entity class to a specific hydrator instance
     *
     * @param  string $class
     * @param  ExtractionInterface|string $hydrator
     * @return self
     */
    public function addHydrator($class, $hydrator)
    {
        if (is_string($hydrator)) {
            /** @var ExtractionInterface $hydratorInstance */
            $hydratorInstance = $this->hydrators->get($hydrator);
            $hydrator         = $hydratorInstance;
        }

        $filteredClass                     = strtolower($class);
        $this->hydratorMap[$filteredClass] = $hydrator;
        return $this;
    }

    /**
     * Set the default hydrator to use if none specified for a class.
     *
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
        $class      = $entity::class;
        $classLower = strtolower($class);

        if (isset($this->hydratorMap[$classLower])) {
            return $this->hydratorMap[$classLower];
        }

        if ($this->metadataMap->has($entity)) {
            $metadata = $this->metadataMap->get($class);
            /** @psalm-suppress PossiblyFalseReference */
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
