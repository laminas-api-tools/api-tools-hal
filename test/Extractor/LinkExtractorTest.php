<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\Http\Request;
use Laminas\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url as UrlHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LinkExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function testExtractGivenIncompleteLinkShouldThrowException(): void
    {
        $linkUrlBuilder = $this->createMock(LinkUrlBuilder::class);
        $linkExtractor = new LinkExtractor($linkUrlBuilder);

        $link = $this->prophesize(Link::class);
        $link->isComplete()->willReturn(false)->shouldBeCalledTimes(1);

        $this->expectException(DomainException::class);
        $linkExtractor->extract($link->reveal());
    }

    public function testExtractGivenLinkWithUrlShouldReturnThisOne(): void
    {
        $linkUrlBuilder = $this->createMock(LinkUrlBuilder::class);
        $linkExtractor = new LinkExtractor($linkUrlBuilder);

        $params = [
            'rel' => 'resource',
            'url' => 'http://api.example.com',
        ];
        $link = Link::factory($params);

        $result = $linkExtractor->extract($link);

        self::assertEquals($params['url'], $result['href']);
    }

    public function testExtractShouldComposeAnyPropertiesInLink(): void
    {
        $linkUrlBuilder = $this->createMock(LinkUrlBuilder::class);
        $linkExtractor = new LinkExtractor($linkUrlBuilder);

        $link = Link::factory([
            'rel'   => 'resource',
            'url'   => 'http://api.example.com/foo?version=2',
            'props' => [
                'version' => 2,
                'latest'  => true,
            ],
        ]);
        $result = $linkExtractor->extract($link);

        $expected = [
            'href'    => 'http://api.example.com/foo?version=2',
            'version' => 2,
            'latest'  => true,
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * @group 95
     */
    public function testPassingFalseReuseParamsOptionShouldOmitMatchedParametersInGeneratedLink(): void
    {
        $serverUrlHelper = $this->createMock(ServerUrl::class);
        $urlHelper       = new UrlHelper();

        $linkUrlBuilder = new LinkUrlBuilder($serverUrlHelper, $urlHelper);

        $linkExtractor = new LinkExtractor($linkUrlBuilder);

        $match = $this->matchUrl('/resource/foo', $urlHelper);
        self::assertEquals('foo', $match->getParam('id', false));

        $link = Link::factory([
            'rel' => 'resource',
            'route' => [
                'name' => 'hostname/resource',
                'options' => [
                    'reuse_matched_params' => false,
                ],
            ],
        ]);

        $result = $linkExtractor->extract($link);

        self::assertIsArray($result);
        self::assertArrayHasKey('href', $result);
        self::assertEquals('http://localhost.localdomain/resource', $result['href']);
    }

    private function matchUrl($url, $urlHelper)
    {
        $url     = 'http://localhost.localdomain' . $url;
        $request = new Request();
        $request->setUri($url);

        $routerClass = \class_exists(V2TreeRouteStack::class) ? V2TreeRouteStack::class : TreeRouteStack::class;
        $router = new $routerClass();

        $router->addRoute('hostname', [
            'type' => 'hostname',
            'options' => [
                'route' => 'localhost.localdomain',
            ],
            'child_routes' => [
                'resource' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/resource[/:id]',
                    ],
                ],
            ],
        ]);

        $match = $router->match($request);
        if ($match instanceof RouteMatch || $match instanceof V2RouteMatch) {
            $urlHelper->setRouter($router);
            $urlHelper->setRouteMatch($match);
        }

        return $match;
    }
}
