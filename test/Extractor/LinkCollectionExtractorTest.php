<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit\Framework\TestCase;

class LinkCollectionExtractorTest extends TestCase
{
    /** @var LinkCollectionExtractor */
    protected $linkCollectionExtractor;

    public function setUp(): void
    {
        $linkExtractor = $this->createMock(LinkExtractor::class);

        $this->linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
    }

    public function testExtractGivenLinkCollectionShouldReturnArrayWithExtractionOfEachLink(): void
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

        self::assertIsArray($result);
        self::assertCount($linkCollection->count(), $result);
    }

    public function testLinkCollectionWithTwoLinksForSameRelationShouldReturnArrayWithOneKeyAggregatingLinks(): void
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

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertIsArray($result['foo']);
        self::assertCount(2, $result['foo']);
    }
}
