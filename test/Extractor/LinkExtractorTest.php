<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\Http\Request;
use Laminas\Mvc\Router\Http\TreeRouteStack;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\View\Helper\Url as UrlHelper;
use PHPUnit_Framework_TestCase as TestCase;

class LinkExtractorTest extends TestCase
{
    public function testExtractGivenIncompleteLinkShouldThrowException()
    {
        $serverUrlHelper = $this->getMock('Laminas\View\Helper\ServerUrl');
        $urlHelper       = $this->getMock('Laminas\View\Helper\Url');

        $linkExtractor = new LinkExtractor($serverUrlHelper, $urlHelper);

        $link = $this->getMockBuilder('Laminas\ApiTools\Hal\Link\Link')
            ->disableOriginalConstructor()
            ->getMock();

        $link
            ->expects($this->once())
            ->method('isComplete')
            ->will($this->returnValue(false));

        $this->setExpectedException('Laminas\ApiTools\ApiProblem\Exception\DomainException');
        $linkExtractor->extract($link);
    }

    public function testExtractGivenLinkWithUrlShouldReturnThisOne()
    {
        $serverUrlHelper = $this->getMock('Laminas\View\Helper\ServerUrl');
        $urlHelper       = $this->getMock('Laminas\View\Helper\Url');

        $linkExtractor = new LinkExtractor($serverUrlHelper, $urlHelper);

        $params = [
            'rel' => 'resource',
            'url' => 'http://api.example.com',
        ];
        $link = Link::factory($params);

        $result = $linkExtractor->extract($link);

        $this->assertEquals($params['url'], $result['href']);
    }

    public function testExtractShouldComposeAnyPropertiesInLink()
    {
        $serverUrlHelper = $this->getMock('Laminas\View\Helper\ServerUrl');
        $urlHelper       = $this->getMock('Laminas\View\Helper\Url');

        $linkExtractor = new LinkExtractor($serverUrlHelper, $urlHelper);

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
        $this->assertEquals($expected, $result);
    }

    /**
     * @group 95
     */
    public function testPassingFalseReuseParamsOptionShouldOmitMatchedParametersInGeneratedLink()
    {
        $serverUrlHelper = $this->getMock('Laminas\View\Helper\ServerUrl');
        $urlHelper       = new UrlHelper;

        $linkExtractor = new LinkExtractor($serverUrlHelper, $urlHelper);

        $match = $this->matchUrl('/resource/foo', $urlHelper);
        $this->assertEquals('foo', $match->getParam('id', false));

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

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('href', $result);
        $this->assertEquals('http://localhost.localdomain/resource', $result['href']);
    }

    private function matchUrl($url, $urlHelper)
    {
        $url     = 'http://localhost.localdomain' . $url;
        $request = new Request();
        $request->setUri($url);

        $router = new TreeRouteStack();

        $router->addRoute('hostname', [
            'type' => 'hostname',
            'options' => [
                'route' => 'localhost.localdomain',
            ],
            'child_routes' => [
                'resource' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/resource[/:id]'
                    ],
                ],
            ]
        ]);

        $match = $router->match($request);
        if ($match instanceof RouteMatch) {
            $urlHelper->setRouter($router);
            $urlHelper->setRouteMatch($match);
        }

        return $match;
    }
}
