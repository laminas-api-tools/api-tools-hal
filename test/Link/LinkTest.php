<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testConstructorTakesLinkRelationName()
    {
        $link = new Link('describedby');
        self::assertEquals('describedby', $link->getRelation());
    }

    public function testCanSetLinkUrl()
    {
        $url  = 'http://example.com/docs.html';
        $link = new Link('describedby');
        $link->setUrl($url);
        self::assertEquals($url, $link->getUrl());
    }

    public function testCanSetLinkRoute()
    {
        $route = 'api/docs';
        $link = new Link('describedby');
        $link->setRoute($route);
        self::assertEquals($route, $link->getRoute());
    }

    public function testCanSetRouteParamsWhenSpecifyingRoute()
    {
        $route  = 'api/docs';
        $params = ['version' => '1.1'];
        $link = new Link('describedby');
        $link->setRoute($route, $params);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($params, $link->getRouteParams());
    }

    public function testCanSetRouteOptionsWhenSpecifyingRoute()
    {
        $route   = 'api/docs';
        $options = ['query' => 'version=1.1'];
        $link = new Link('describedby');
        $link->setRoute($route, null, $options);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($options, $link->getRouteOptions());
    }

    public function testCanSetRouteParamsSeparately()
    {
        $route  = 'api/docs';
        $params = ['version' => '1.1'];
        $link = new Link('describedby');
        $link->setRoute($route);
        $link->setRouteParams($params);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($params, $link->getRouteParams());
    }

    public function testCanSetRouteOptionsSeparately()
    {
        $route   = 'api/docs';
        $options = ['query' => 'version=1.1'];
        $link = new Link('describedby');
        $link->setRoute($route);
        $link->setRouteOptions($options);
        self::assertEquals($route, $link->getRoute());
        self::assertEquals($options, $link->getRouteOptions());
    }

    public function testSettingUrlAfterSettingRouteRaisesException()
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');

        $this->expectException(DomainException::class);
        $link->setUrl('http://example.com/api/docs.html');
    }

    public function testSettingRouteAfterSettingUrlRaisesException()
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');

        $this->expectException(DomainException::class);
        $link->setRoute('api/docs');
    }

    public function testIsCompleteReturnsFalseIfNeitherUrlNorRouteIsSet()
    {
        $link = new Link('describedby');
        self::assertFalse($link->isComplete());
    }

    public function testHasUrlReturnsFalseWhenUrlIsNotSet()
    {
        $link = new Link('describedby');
        self::assertFalse($link->hasUrl());
    }

    public function testHasUrlReturnsTrueWhenUrlIsSet()
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');
        self::assertTrue($link->hasUrl());
    }

    public function testIsCompleteReturnsTrueWhenUrlIsSet()
    {
        $link = new Link('describedby');
        $link->setUrl('http://example.com/api/docs.html');
        self::assertTrue($link->isComplete());
    }

    public function testHasRouteReturnsFalseWhenRouteIsNotSet()
    {
        $link = new Link('describedby');
        self::assertFalse($link->hasRoute());
    }

    public function testHasRouteReturnsTrueWhenRouteIsSet()
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');
        self::assertTrue($link->hasRoute());
    }

    public function testIsCompleteReturnsTrueWhenRouteIsSet()
    {
        $link = new Link('describedby');
        $link->setRoute('api/docs');
        self::assertTrue($link->isComplete());
    }

    /**
     * @group 79
     */
    public function testFactoryCanGenerateLinkWithUrl()
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
    public function testFactoryCanGenerateLinkWithRouteInformation()
    {
        $rel     = 'describedby';
        $route   = 'api/docs';
        $params  = ['version' => '1.1'];
        $options = ['query' => 'version=1.1'];
        $link = Link::factory([
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

    public function testFactoryCanGenerateLinkWithArbitraryProperties()
    {
        $rel = 'describedby';
        $url = 'http://example.org/api/foo?version=2';
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
