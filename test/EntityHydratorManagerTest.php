<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @subpackage UnitTest
 */
class EntityHydratorManagerTest extends TestCase
{
    public function testAddHydratorGivenEntityClassAndHydratorInstanceShouldAssociateThem()
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = 'LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator';
        $hydrator      = new $hydratorClass();

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator(
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity',
            $hydrator
        );

        $entityHydrator = $entityHydratorManager->getHydratorForEntity($entity);
        $this->assertInstanceOf($hydratorClass, $entityHydrator);
        $this->assertSame($hydrator, $entityHydrator);
    }

    public function testAddHydratorGivenEntityAndHydratorClassesShouldAssociateThem()
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = 'LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator';

        $metadataMap = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator(
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity',
            $hydratorClass
        );

        $this->assertInstanceOf(
            $hydratorClass,
            $entityHydratorManager->getHydratorForEntity($entity)
        );
    }

    public function testAddHydratorDoesntFailWithAutoInvokables()
    {
        $metadataMap           = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->addHydrator('stdClass', 'LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator');

        $this->assertInstanceOf(
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator',
            $entityHydratorManager->getHydratorForEntity(new \stdClass)
        );
    }

    public function testGetHydratorForEntityGivenEntityDefinedInMetadataMapShouldReturnDefaultHydrator()
    {
        $entity        = new TestAsset\Entity('foo', 'Foo Bar');
        $hydratorClass = 'LaminasTest\ApiTools\Hal\Plugin\TestAsset\DummyHydrator';

        $metadataMap = new MetadataMap([
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity' => [
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

    public function testGetHydratorForEntityGivenUnkownEntityShouldReturnDefaultHydrator()
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $defaultHydrator = new DummyHydrator();

        $metadataMap           = new MetadataMap();
        $metadataMap->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadataMap);

        $entityHydratorManager->setDefaultHydrator($defaultHydrator);

        $entityHydrator = $entityHydratorManager->getHydratorForEntity($entity);

        $this->assertSame($defaultHydrator, $entityHydrator);
    }

    public function testGetHydratorForEntityGivenUnkownEntityAndNoDefaultHydratorDefinedShouldReturnFalse()
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
