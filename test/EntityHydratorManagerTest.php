<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\HydratorPluginManagerInterface;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @subpackage UnitTest
 */
class EntityHydratorManagerTest extends TestCase
{
    /** @var string */
    private $hydratorClass;

    public function setUp(): void
    {
        $this->hydratorClass = \interface_exists(HydratorPluginManagerInterface::class)
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
        $this->assertInstanceOf($hydratorClass, $entityHydrator);
        $this->assertSame($hydrator, $entityHydrator);
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

        $this->assertInstanceOf(
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

        $this->assertInstanceOf(
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

        $this->assertInstanceOf(
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

        $this->assertSame($defaultHydrator, $entityHydrator);
    }

    public function testGetHydratorForEntityGivenUnknownEntityAndNoDefaultHydratorDefinedShouldReturnFalse(): void
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');

        $metadataMap           = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $hydrator = $entityHydratorManager->getHydratorForEntity($entity);

        $this->assertFalse($hydrator);
    }
}
