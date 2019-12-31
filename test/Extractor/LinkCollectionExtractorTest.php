<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit_Framework_TestCase as TestCase;

class LinkCollectionExtractorTest extends TestCase
{
    protected $linkCollectionExtractor;

    public function setUp()
    {
        $linkExtractor = $this->getMockBuilder('Laminas\ApiTools\Hal\Extractor\LinkExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
    }

    public function testExtractGivenLinkCollectionShouldReturnArrayWithExtractionOfEachLink()
    {
        $linkCollection = new LinkCollection();
        $linkCollection->add(Link::factory([
            'rel' => 'foo',
            'url' => 'http://example.com/foo',
        ]));
        $linkCollection->add(Link::factory([
            'rel' => 'bar',
            'url' => 'http://example.com/bar',
        ]));
        $linkCollection->add(Link::factory([
            'rel' => 'baz',
            'url' => 'http://example.com/baz',
        ]));

        $result = $this->linkCollectionExtractor->extract($linkCollection);

        $this->assertInternalType('array', $result);
        $this->assertCount($linkCollection->count(), $result);
    }

    public function testLinkCollectionWithTwoLinksForSameRelationShouldReturnArrayWithOneKeyAggregatingLinks()
    {
        $linkCollection = new LinkCollection();
        $linkCollection->add(Link::factory([
            'rel' => 'foo',
            'url' => 'http://example.com/foo',
        ]));
        $linkCollection->add(Link::factory([
            'rel' => 'foo',
            'url' => 'http://example.com/bar',
        ]));
        $linkCollection->add(Link::factory([
            'rel' => 'baz',
            'url' => 'http://example.com/baz',
        ]));

        $result = $this->linkCollectionExtractor->extract($linkCollection);

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $this->assertInternalType('array', $result['foo']);
        $this->assertCount(2, $result['foo']);
    }
}
