<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use ArrayObject;
use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Extractor\EntityExtractor;
use Laminas\Hydrator\ObjectProperty;
use Laminas\Hydrator\ObjectPropertyHydrator;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit\Framework\TestCase;

/**
 * @subpackage UnitTest
 */
class EntityExtractorTest extends TestCase
{
    /** @var string */
    private $hydratorClass;

    public function setUp()
    {
        $this->hydratorClass = class_exists(ObjectPropertyHydrator::class)
            ? ObjectPropertyHydrator::class
            : ObjectProperty::class;
    }

    public function testExtractGivenEntityWithAssociateHydratorShouldExtractData()
    {
        $hydrator = new $this->hydratorClass();

        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn($hydrator);

        $extractor = new EntityExtractor($entityHydratorManager->reveal());

        $this->assertSame($extractor->extract($entity), $hydrator->extract($entity));
    }

    public function testExtractGivenEntityWithoutAssociateHydratorShouldExtractPublicProperties()
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null);

        $extractor = new EntityExtractor($entityHydratorManager->reveal());
        $data = $extractor->extract($entity);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('doNotExportMe', $data);
    }

    public function testExtractTwiceGivenSameEntityShouldProcessExtractionOnceAndReturnSameData()
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null)->shouldBeCalledTimes(1);

        $extractor = new EntityExtractor($entityHydratorManager->reveal());

        $data1 = $extractor->extract($entity);
        $data2 = $extractor->extract($entity);

        $this->assertSame($data1, $data2);
    }

    public function testExtractOfArrayObjectEntityWillExtractCorrectly()
    {
        $data   = ['id' => 'foo', 'message' => 'FOO'];
        $entity = new ArrayObject($data);
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null)->shouldBeCalledTimes(1);

        $extractor = new EntityExtractor($entityHydratorManager->reveal());

        $this->assertSame($data, $extractor->extract($entity));
    }
}
