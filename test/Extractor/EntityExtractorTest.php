<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Extractor\EntityExtractor;
use Laminas\Stdlib\Hydrator\ObjectProperty;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @subpackage UnitTest
 */
class EntityExtractorTest extends TestCase
{
    public function testExtractGivenEntityWithAssociateHydratorShouldExtractData()
    {
        $hydrator = new ObjectProperty();

        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->getMockBuilder('Laminas\ApiTools\Hal\EntityHydratorManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityHydratorManager
            ->method('getHydratorForEntity')
            ->with($entity)
            ->will($this->returnValue($hydrator));

        $extractor = new EntityExtractor($entityHydratorManager);

        $this->assertSame($extractor->extract($entity), $hydrator->extract($entity));
    }

    public function testExtractGivenEntityWithoutAssociateHydratorShouldExtractPublicProperties()
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->getMockBuilder('Laminas\ApiTools\Hal\EntityHydratorManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityHydratorManager
            ->method('getHydratorForEntity')
            ->with($entity)
            ->will($this->returnValue(null));

        $extractor = new EntityExtractor($entityHydratorManager);
        $data = $extractor->extract($entity);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('doNotExportMe', $data);
    }

    public function testExtractTwiceGivenSameEntityShouldProcessExtractionOnceAndReturnSameData()
    {
        $entity = new TestAsset\Entity('foo', 'Foo Bar');
        $entityHydratorManager = $this->getMockBuilder('Laminas\ApiTools\Hal\EntityHydratorManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityHydratorManager
            ->expects($this->once())
            ->method('getHydratorForEntity')
            ->with($entity)
            ->will($this->returnValue(null));

        $extractor = new EntityExtractor($entityHydratorManager);

        $data1 = $extractor->extract($entity);
        $data2 = $extractor->extract($entity);

        $this->assertSame($data1, $data2);
    }
}
