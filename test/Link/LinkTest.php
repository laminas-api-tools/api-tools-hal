<?php

namespace LaminasTest\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testConstructorTakesLinkRelationName(): void
    {
        $link = new Link('describedby');
        self::assertEquals('describedby', $link->getRelation());
    }

    public function testCanSetLinkUrl(): void
    {
        $url  = 'http://example.com/docs.html';
        $link = new Link('describedby');
        $link->setUrl($url);
        self::assertEquals($url, $link->getUrl());
    }

    public function testCanSetLinkRoute(): void
    {
        $route = 'api/docs';
        $link  = new Link('describedby');
        $link->setRoute($route);
        self::assertEquals($route, $link->getRoute());
    }

    public function testCanSetRouteParamsWhenSpecifyingRoute(): void
    {
        $route  = 'api/docs';
        $params = ['version' => '1.1'];
        $link   = new Link('describedby');
        $link->setRoute($route, $params);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($params, $link->getRouteParams());
    }

    public function testCanSetRouteOptionsWhenSpecifyingRoute(): void
    {
        $route   = 'api/docs';
        $options = ['query' => 'version=1.1'];
        $link    = new Link('describedby');
        $link->setRoute($route, null, $options);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($options, $link->getRouteOptions());
    }

    public function testCanSetRouteParamsSeparately(): void
    {
        $route  = 'api/docs';
        $params = ['version' => '1.1'];
        $link   = new Link('describedby');
        $link->setRoute($route);
        $link->setRouteParams($params);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($params, $link->getRouteParams());
    }

    public function testCanSetRouteOptionsSeparately(): void
    {
        $route   = 'api/docs';
        $options = ['query' => 'version=1.1'];
        $link    = new Link('describedby');
        $link->setRoute($route);
        $link->setRouteOptions($options);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($options, $link->getRouteOptions());
    }

    public function testSettingUrlAfterSettingRouteRaisesException(): void
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');

        $this->expectException(DomainException::class);
        $link->setUrl('http://example.com/api/docs.html');
    }

    public function testSettingRouteAfterSettingUrlRaisesException(): void
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');

        $this->expectException(DomainException::class);
        $link->setRoute('api/docs');
    }

    public function testIsCompleteReturnsFalseIfNeitherUrlNorRouteIsSet(): void
    {
        $link = new Link('describedby');
        self::assertFalse($link->isComplete());
    }

    public function testHasUrlReturnsFalseWhenUrlIsNotSet(): void
    {
        $link = new Link('describedby');
        self::assertFalse($link->hasUrl());
    }

    public function testHasUrlReturnsTrueWhenUrlIsSet(): void
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');
        self::assertTrue($link->hasUrl());
    }

    public function testIsCompleteReturnsTrueWhenUrlIsSet(): void
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');
        self::assertTrue($link->isComplete());
    }

    public function testHasRouteReturnsFalseWhenRouteIsNotSet(): void
    {
        $link = new Link('describedby');
        self::assertFalse($link->hasRoute());
    }

    public function testHasRouteReturnsTrueWhenRouteIsSet(): void
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');
        self::assertTrue($link->hasRoute());
    }

    public function testIsCompleteReturnsTrueWhenRouteIsSet(): void
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');
        self::assertTrue($link->isComplete());
    }

    /**
     * @group 79
     */
    public function testFactoryCanGenerateLinkWithUrl(): void
    {
        $rel  = 'describedby';
        $url  = 'http://example.com/docs.html';
        $link = Link::factory([
            'rel' => $rel,
            'url' => $url,
        ]);
        self::assertInstanceOf(Link::class, $link);
        self::assertEquals($rel, $link->getRelation());
        self::assertEquals($url, $link->getUrl());
    }

    /**
     * @group 79
     */
    public function testFactoryCanGenerateLinkWithRouteInformation(): void
    {
        $rel     = 'describedby';
        $route   = 'api/docs';
        $params  = ['version' => '1.1'];
        $options = ['query' => 'version=1.1'];
        $link    = Link::factory([
            'rel'   => $rel,
            'route' => [
                'name'    => $route,
                'params'  => $params,
                'options' => $options,
            ],
        ]);

        self::assertInstanceOf(Link::class, $link);
        self::assertEquals('describedby', $link->getRelation());
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($params, $link->getRouteParams());
        self::assertEquals($options, $link->getRouteOptions());
    }

    public function testFactoryCanGenerateLinkWithArbitraryProperties(): void
    {
        $rel  = 'describedby';
        $url  = 'http://example.org/api/foo?version=2';
        $link = Link::factory([
            'rel'   => $rel,
            'url'   => $url,
            'props' => [
                'version' => 2,
                'latest'  => true,
            ],
        ]);

        self::assertInstanceOf(Link::class, $link);
        self::assertEquals('describedby', $link->getRelation());
        $props = $link->getProps();
        self::assertEquals([
            'version' => 2,
            'latest'  => true,
        ], $props);
    }
}
