<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Extractor;

use ArrayObject;
use Laminas\ApiTools\Hal\EntityHydratorManager;
use laminas\apitools\hal\extractor\entityextractor;
use Laminas\Hydrator\ObjectProperty;
use Laminas\Hydrator\ObjectPropertyHydrator;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

use function class_exists;

class EntityExtractorTest extends TestCase
{
    use ProphecyTrait;

    /** @var string */
    private $hydratorClass;

    public function setUp(): void
    {
        $this->hydratorClass = class_exists(ObjectPropertyHydrator::class)
            ? ObjectPropertyHydrator::class
            : ObjectProperty::class;
    }

    public function testExtractGivenEntityWithAssociateHydratorShouldExtractData(): void
    {
        $hydrator = new $this->hydratorClass();

        $entity                = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn($hydrator);

        $extractor = new entityextractor($entityHydratorManager->reveal());

        self::assertSame($extractor->extract($entity), $hydrator->extract($entity));
    }

    public function testExtractGivenEntityWithoutAssociateHydratorShouldExtractPublicProperties(): void
    {
        $entity                = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null);

        $extractor = new entityextractor($entityHydratorManager->reveal());
        $data      = $extractor->extract($entity);

        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayNotHasKey('doNotExportMe', $data);
    }

    public function testExtractTwiceGivenSameEntityShouldProcessExtractionOnceAndReturnSameData(): void
    {
        $entity                = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null)->shouldBeCalledTimes(1);

        $extractor = new entityextractor($entityHydratorManager->reveal());

        $data1 = $extractor->extract($entity);
        $data2 = $extractor->extract($entity);

        self::assertSame($data1, $data2);
    }

    public function testExtractOfArrayObjectEntityWillExtractCorrectly(): void
    {
        $data                  = ['id' => 'foo', 'message' => 'FOO'];
        $entity                = new ArrayObject($data);
        $entityHydratorManager = $this->prophesize(EntityHydratorManager::class);
        $entityHydratorManager->getHydratorForEntity($entity)->willReturn(null)->shouldBeCalledTimes(1);

        $extractor = new entityextractor($entityHydratorManager->reveal());

        $this->assertSame($data, $extractor->extract($entity));
    }
}
