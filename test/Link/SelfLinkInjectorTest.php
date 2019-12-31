<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Link;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use Laminas\ApiTools\Hal\Link\SelfLinkInjector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SelfLinkInjectorTest extends TestCase
{
    public function testInjectSelfLinkAlreadyAddedShouldBePrevented()
    {
        $linkCollection = $this->prophesize(LinkCollection::class);
        $linkCollection->has('self')->willReturn(true);
        $linkCollection->add(Argument::any())->shouldNotBeCalled();

        $resource = new Entity([]);
        $resource->setLinks($linkCollection->reveal());

        $injector = new SelfLinkInjector();
        $injector->injectSelfLink($resource, 'foo');
    }

    public function testInjectEntitySelfLinkShouldAddSelfLinkToLinkCollection()
    {
        $linkCollection = new LinkCollection();

        $resource = new Entity([]);
        $resource->setLinks($linkCollection);

        $injector = new SelfLinkInjector();
        $injector->injectSelfLink($resource, 'foo');

        $this->assertTrue($linkCollection->has('self'));
    }

    public function testInjectCollectionSelfLinkShouldAddSelfLinkToLinkCollection()
    {
        $linkCollection = new LinkCollection();

        $resource = new Collection([]);
        $resource->setLinks($linkCollection);

        $injector = new SelfLinkInjector();
        $injector->injectSelfLink($resource, 'foo');

        $this->assertTrue($linkCollection->has('self'));
    }

    public function testInjectEntitySelfLinkWithIdentifierShouldAddSelfLinkWithIdentifierRouteParam()
    {
        $routeIdentifier = 'id';

        $linkCollection = new LinkCollection();

        $resource = new Entity([], 123);
        $resource->setLinks($linkCollection);

        $injector = new SelfLinkInjector();
        $injector->injectSelfLink($resource, 'foo', $routeIdentifier);

        $this->assertTrue($linkCollection->has('self'));

        $selfLink = $linkCollection->get('self');
        $linkRouteParams = $selfLink->getRouteParams();

        $this->assertArrayHasKey($routeIdentifier, $linkRouteParams);
    }
}
