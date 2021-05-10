<?php

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\HydratorPluginManagerInterface;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit\Framework\TestCase;
use stdClass;

use function interface_exists;

class EntityHydratorManagerTest extends TestCase
{
    /** @var string */
    private $hydratorClass;

    public function setUp(): void
    {
        $this->hydratorClass = interface_exists(HydratorPluginManagerInterface::class)
            ? TestAsset\DummyV3Hydrator::class
            : TestAsset\DummyHydrator::class;
    }

    public function testAddHydratorGivenEntityClassAndHydratorInstanceShouldAssociateThem(): void
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = $this->hydratorClass;
        $hydrator      = new $hydratorClass();

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator(TestAsset\Entity::class, $hydrator);

        $entityHydrator = $entityHydratorManager->getHydratorForEntity($entity);
        self::assertInstanceOf($hydratorClass, $entityHydrator);
        self::assertSame($hydrator, $entityHydrator);
    }

    public function testAddHydratorGivenEntityAndHydratorClassesShouldAssociateThem(): void
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = $this->hydratorClass;

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator(TestAsset\Entity::class, $hydratorClass);

        self::assertInstanceOf(
            $hydratorClass,
            $entityHydratorManager->getHydratorForEntity($entity)
        );
    }

    public function testAddHydratorDoesntFailWithAutoInvokables(): void
    {
        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator(stdClass::class, $this->hydratorClass);

        self::assertInstanceOf(
            $this->hydratorClass,
            $entityHydratorManager->getHydratorForEntity(new stdClass())
        );
    }

    public function testGetHydratorForEntityGivenEntityDefinedInMetadataMapShouldReturnDefaultHydrator(): void
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = $this->hydratorClass;

        $metadataMap = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator' => $hydratorClass,
            ],
        ]);

        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        self::assertInstanceOf(
            $hydratorClass,
            $entityHydratorManager->getHydratorForEntity($entity)
        );
    }

    public function testGetHydratorForEntityGivenUnknownEntityShouldReturnDefaultHydrator(): void
    {
        $entity          = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass   = $this->hydratorClass;
        $defaultHydrator = new $hydratorClass();

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->setDefaultHydrator($defaultHydrator);

        $entityHydrator = $entityHydratorManager->getHydratorForEntity($entity);

        self::assertSame($defaultHydrator, $entityHydrator);
    }

    public function testGetHydratorForEntityGivenUnknownEntityAndNoDefaultHydratorDefinedShouldReturnFalse(): void
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $hydrator = $entityHydratorManager->getHydratorForEntity($entity);

        self::assertFalse($hydrator);
    }
}
