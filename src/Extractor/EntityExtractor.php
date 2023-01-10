<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Extractor;

use ArrayObject;
use JsonSerializable;
use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\Hydrator\ExtractionInterface;
use SplObjectStorage;

use function get_object_vars;

/**
 * Extract entities.
 */
class EntityExtractor implements ExtractionInterface
{
    /** @var EntityHydratorManager */
    protected $entityHydratorManager;

    /**
     * Map of entities to their Laminas\ApiTools\Hal\Entity serializations
     *
     * @var SplObjectStorage
     */
    protected $serializedEntities;

    public function __construct(EntityHydratorManager $entityHydratorManager)
    {
        $this->entityHydratorManager = $entityHydratorManager;
        $this->serializedEntities    = new SplObjectStorage();
    }

    /**
     * @inheritDoc
     */
    public function extract(object $object): array
    {
        if (isset($this->serializedEntities[$object])) {
            /** @psalm-var array<array-key, mixed> */
            return $this->serializedEntities[$object];
        }

        $this->serializedEntities[$object] = $this->extractEntity($object);

        /** @psalm-var array<array-key, mixed> */
        return $this->serializedEntities[$object];
    }

    private function extractEntity(object $entity): array
    {
        $hydrator = $this->entityHydratorManager->getHydratorForEntity($entity);

        if ($hydrator) {
            return $hydrator->extract($entity);
        }

        if ($entity instanceof JsonSerializable) {
            /** @psalm-var array<array-key, mixed> */
            return $entity->jsonSerialize();
        }

        if ($entity instanceof ArrayObject) {
            return $entity->getArrayCopy();
        }

        return get_object_vars($entity);
    }
}
