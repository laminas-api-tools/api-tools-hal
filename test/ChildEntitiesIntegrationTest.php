<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ApiTools\Hal\Plugin\Hal as HalHelper;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;
use Laminas\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\ServerUrl as ServerUrlHelper;
use Laminas\View\Helper\Url as UrlHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

/**
 * @subpackage UnitTest
 */
class ChildEntitiesIntegrationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var V2TreeRouteStack|TreeRouteStack
     */
    protected $router;

    /**
     * @var HelperPluginManager
     */
    protected $helpers;

    /**
     * @var HalJsonRenderer
     */
    protected $renderer;

    /**
     * @var ControllerPluginManager
     */
    protected $plugins;

    public function setUp(): void
    {
        $this->setupRouter();
        $this->setupHelpers();
        $this->setupRenderer();
    }

    public function setupHelpers(): void
    {
        if (! $this->router) {
            $this->setupRouter();
        }

        $urlHelper = new UrlHelper();
        $urlHelper->setRouter($this->router);

        $serverUrlHelper = new ServerUrlHelper();
        $serverUrlHelper->setScheme('http');
        $serverUrlHelper->setHost('localhost.localdomain');

        $linkUrlBuilder = new LinkUrlBuilder($serverUrlHelper, $urlHelper);

        $linksHelper = new HalHelper();
        $linksHelper->setLinkUrlBuilder($linkUrlBuilder);

        $linkExtractor = new LinkExtractor($linkUrlBuilder);
        $linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
        $linksHelper->setLinkCollectionExtractor($linkCollectionExtractor);

        $this->helpers = $helpers = new HelperPluginManager(new ServiceManager());
        $helpers->setService('url', $urlHelper);
        $helpers->setService('serverUrl', $serverUrlHelper);
        $helpers->setService('Hal', $linksHelper);

        $this->plugins = $plugins = new ControllerPluginManager(new ServiceManager());
        $plugins->setService('Hal', $linksHelper);
    }

    public function setupRenderer(): void
    {
        if (! $this->helpers) {
            $this->setupHelpers();
        }
        $this->renderer = $renderer = new HalJsonRenderer(new ApiProblemRenderer());
        $renderer->setHelperPluginManager($this->helpers);
    }

    public function setupRouter(): void
    {
        $routes = [
            'parent' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/api/parent[/:parent]',
                    'defaults' => [
                        'controller' => 'Api\ParentController',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/child[/:child]',
                            'defaults' => [
                                'controller' => 'Api\ChildController',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $class = \class_exists(V2TreeRouteStack::class) ? V2TreeRouteStack::class : TreeRouteStack::class;

        $this->router = $router = new $class();
        $router->addRoutes($routes);
    }

    public function setUpParentEntity(): Entity
    {
        $parent       = new stdClass();
        $parent->id   = 'anakin';
        $parent->name = 'Anakin Skywalker';
        $entity       = new Entity($parent, 'anakin');
        $link         = new Link('self');

        $link->setRoute('parent');
        $link->setRouteParams(['parent' => 'anakin']);
        $entity->getLinks()->add($link);

        return $entity;
    }

    public function setUpChildEntity($id, $name): Entity
    {
        $child       = new stdClass();
        $child->id   = $id;
        $child->name = $name;
        $entity      = new Entity($child, $id);
        $link        = new Link('self');

        $link->setRoute('parent/child');
        $link->setRouteParams(['child' => $id]);
        $entity->getLinks()->add($link);

        return $entity;
    }

    public function setUpChildCollection(): Collection
    {
        $children = [
            ['luke', 'Luke Skywalker'],
            ['leia', 'Leia Organa'],
        ];
        $collection1 = [];
        foreach ($children as $info) {
            $collection1[] = \call_user_func_array([$this, 'setUpChildEntity'], $info);
        }
        $collection = new Collection($collection1);
        $collection->setCollectionRoute('parent/child');
        $collection->setEntityRoute('parent/child');
        $collection->setPage(1);
        $collection->setPageSize(10);
        $collection->setCollectionName('child');

        $link = new Link('self');
        $link->setRoute('parent/child');
        $collection->getLinks()->add($link);

        return $collection;
    }

    public function testParentEntityRendersAsExpected(): void
    {
        $uri = 'http://localhost.localdomain/api/parent/anakin';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $routeClass = \class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        self::assertInstanceOf($routeClass, $matches);
        self::assertEquals('anakin', $matches->getParam('parent'));
        self::assertEquals('parent', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $parent = $this->setUpParentEntity();
        $model  = new HalJsonModel();
        $model->setPayload($parent);

        $json = $this->renderer->render($model);
        $test = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        self::assertObjectHasAttribute('_links', $test);
        self::assertObjectHasAttribute('self', $test->_links);
        self::assertObjectHasAttribute('href', $test->_links->self);
        self::assertEquals('http://localhost.localdomain/api/parent/anakin', $test->_links->self->href);
    }

    public function testChildEntityRendersAsExpected(): void
    {
        $uri = 'http://localhost.localdomain/api/parent/anakin/child/luke';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $routeClass = \class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        self::assertInstanceOf($routeClass, $matches);
        self::assertEquals('anakin', $matches->getParam('parent'));
        self::assertEquals('luke', $matches->getParam('child'));
        self::assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $child = $this->setUpChildEntity('luke', 'Luke Skywalker');
        $model = new HalJsonModel();
        $model->setPayload($child);

        $json = $this->renderer->render($model);
        $test = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        self::assertObjectHasAttribute('_links', $test);
        self::assertObjectHasAttribute('self', $test->_links);
        self::assertObjectHasAttribute('href', $test->_links->self);
        self::assertEquals('http://localhost.localdomain/api/parent/anakin/child/luke', $test->_links->self->href);
    }

    public function testChildCollectionRendersAsExpected(): void
    {
        $uri = 'http://localhost.localdomain/api/parent/anakin/child';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $routeClass = \class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        self::assertInstanceOf($routeClass, $matches);
        self::assertEquals('anakin', $matches->getParam('parent'));
        self::assertNull($matches->getParam('child'));
        self::assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $collection = $this->setUpChildCollection();
        $model = new HalJsonModel();
        $model->setPayload($collection);

        $json = $this->renderer->render($model);
        $test = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        self::assertObjectHasAttribute('_links', $test);
        self::assertObjectHasAttribute('self', $test->_links);
        self::assertObjectHasAttribute('href', $test->_links->self);
        self::assertEquals('http://localhost.localdomain/api/parent/anakin/child', $test->_links->self->href);

        self::assertObjectHasAttribute('_embedded', $test);
        self::assertObjectHasAttribute('child', $test->_embedded);
        self::assertIsArray($test->_embedded->child);
        self::assertCount(2, $test->_embedded->child);

        foreach ($test->_embedded->child as $child) {
            self::assertObjectHasAttribute('_links', $child);
            self::assertObjectHasAttribute('self', $child->_links);
            self::assertObjectHasAttribute('href', $child->_links->self);

            self::assertMatchesRegularExpression(
                '#^http://localhost.localdomain/api/parent/anakin/child/[^/]+$#',
                $child->_links->self->href
            );
        }
    }

    public function setUpAlternateRouter(): void
    {
        $routes = [
            'parent' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/api/parent[/:id]',
                    'defaults' => [
                        'controller' => 'Api\ParentController',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/child[/:child]',
                            'defaults' => [
                                'controller' => 'Api\ChildController',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $class = \class_exists(V2TreeRouteStack::class) ? V2TreeRouteStack::class : TreeRouteStack::class;
        $this->router = $router = new $class();
        $router->addRoutes($routes);
        $this->helpers->get('url')->setRouter($router);
    }

    public function testChildEntityObjectIdentifierMapping(): void
    {
        $this->setUpAlternateRouter();

        $uri = 'http://localhost.localdomain/api/parent/anakin/child/luke';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $routeClass = \class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        self::assertInstanceOf($routeClass, $matches);
        self::assertEquals('anakin', $matches->getParam('id'));
        self::assertEquals('luke', $matches->getParam('child'));
        self::assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $child = $this->setUpChildEntity('luke', 'Luke Skywalker');
        $model = new HalJsonModel();
        $model->setPayload($child);

        $json = $this->renderer->render($model);
        $test = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        self::assertObjectHasAttribute('_links', $test);
        self::assertObjectHasAttribute('self', $test->_links);
        self::assertObjectHasAttribute('href', $test->_links->self);
        self::assertEquals('http://localhost.localdomain/api/parent/anakin/child/luke', $test->_links->self->href);
    }

    public function testChildEntityIdentifierMappingInsideCollection(): void
    {
        $this->setUpAlternateRouter();

        $uri = 'http://localhost.localdomain/api/parent/anakin/child';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $routeClass = \class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        self::assertInstanceOf($routeClass, $matches);
        self::assertEquals('anakin', $matches->getParam('id'));
        self::assertNull($matches->getParam('child_id'));
        self::assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $collection = $this->setUpChildCollection();
        $model = new HalJsonModel();
        $model->setPayload($collection);

        $json = $this->renderer->render($model);
        $test = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        self::assertObjectHasAttribute('_links', $test);
        self::assertObjectHasAttribute('self', $test->_links);
        self::assertObjectHasAttribute('href', $test->_links->self);
        self::assertEquals('http://localhost.localdomain/api/parent/anakin/child', $test->_links->self->href);

        self::assertObjectHasAttribute('_embedded', $test);
        self::assertObjectHasAttribute('child', $test->_embedded);
        self::assertIsArray($test->_embedded->child);
        self::assertCount(2, $test->_embedded->child);

        foreach ($test->_embedded->child as $child) {
            self::assertObjectHasAttribute('_links', $child);
            self::assertObjectHasAttribute('self', $child->_links);
            self::assertObjectHasAttribute('href', $child->_links->self);
            self::assertMatchesRegularExpression(
                '#^http://localhost.localdomain/api/parent/anakin/child/[^/]+$#',
                $child->_links->self->href
            );
        }
    }
}
