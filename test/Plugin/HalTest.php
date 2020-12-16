<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Plugin;

use ArrayObject;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Exception;
use Laminas\ApiTools\Hal\Exception\CircularReferenceException;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\ApiTools\Hal\Plugin\Hal as HalHelper;
use Laminas\EventManager\Event;
use Laminas\Hydrator;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\Exception as V2RouterException;
use Laminas\Mvc\Router\Http\Segment as V2Segment;
use Laminas\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Laminas\Paginator\Adapter\ArrayAdapter as ArrayPaginator;
use Laminas\Paginator\Paginator;
use Laminas\Router\Exception as RouterException;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Uri\Http;
use Laminas\View\Helper\ServerUrl as ServerUrlHelper;
use Laminas\View\Helper\Url as UrlHelper;
use LaminasTest\ApiTools\Hal\TestAsset as HalTestAsset;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionObject;

/**
 * @subpackage UnitTest
 */
class HalTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var HalHelper
     */
    protected $plugin;

    /**
     * @var V2TreeRouteStack|TreeRouteStack
     */
    protected $router;

    /**
     * @var ServerUrlHelper
     */
    protected $serverUrlHelper;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    public function setUp(): void
    {
        $routerClass  = \class_exists(V2TreeRouteStack::class) ? V2TreeRouteStack::class : TreeRouteStack::class;
        $routeClass   = \class_exists(V2Segment::class) ? V2Segment::class : Segment::class;

        $this->router = $router = new $routerClass();
        $route = new $routeClass('/resource[/[:id]]');
        $router->addRoute('resource', $route);
        $route2 = new $routeClass('/help');
        $router->addRoute('docs', $route2);
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
                    'may_terminate' => true,
                    'child_routes' => [
                        'children' => [
                            'type' => 'literal',
                            'options' => [
                                'route' => '/children',
                            ],
                        ],
                    ],
                ],
                'users' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/users[/:id]',
                    ],
                ],
                'contacts' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/contacts[/:id]',
                    ],
                ],
                'embedded' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/embedded[/:id]',
                    ],
                ],
                'embedded_custom' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/embedded_custom[/:custom_id]',
                    ],
                ],
            ],
        ]);

        $this->event = $event = new MvcEvent();
        $event->setRouter($router);
        $router->setRequestUri(new Http('http://localhost.localdomain/resource'));

        $controller = $this->controller = $this->prophesize(AbstractRestfulController::class);
        $controller->getEvent()->willReturn($event);

        $this->urlHelper = $urlHelper = new UrlHelper();
        $urlHelper->setRouter($router);

        $this->serverUrlHelper = $serverUrlHelper = new ServerUrlHelper();
        $serverUrlHelper->setScheme('http');
        $serverUrlHelper->setHost('localhost.localdomain');

        $this->plugin = $plugin = new HalHelper();
        $plugin->setController($controller->reveal());

        $linkUrlBuilder = new LinkUrlBuilder($serverUrlHelper, $urlHelper);
        $plugin->setLinkUrlBuilder($linkUrlBuilder);

        $linkExtractor = new LinkExtractor($linkUrlBuilder);
        $linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
        $plugin->setLinkCollectionExtractor($linkCollectionExtractor);
    }

    public function getArraySerializableHydratorClass(): string
    {
        return \class_exists(Hydrator\ArraySerializableHydrator::class)
            ? Hydrator\ArraySerializableHydrator::class
            : Hydrator\ArraySerializable::class;
    }

    public function getObjectPropertyHydratorClass(): string
    {
        return \class_exists(Hydrator\ObjectPropertyHydrator::class)
            ? Hydrator\ObjectPropertyHydrator::class
            : Hydrator\ObjectProperty::class;
    }

    public function assertRelationalLinkContains($match, $relation, $entity): void
    {
        self::assertIsArray($entity);
        self::assertArrayHasKey('_links', $entity);
        $links = $entity['_links'];
        self::assertIsArray($links);
        self::assertArrayHasKey($relation, $links);
        $link = $links[$relation];
        self::assertIsArray($link);
        self::assertArrayHasKey('href', $link);
        $href = $link['href'];
        self::assertIsString($href);
        self::assertStringContainsString($match, $href);
    }

    public function testCreateLinkSkipServerUrlHelperIfSchemeExists(): void
    {
        $url = $this->plugin->createLink('hostname/resource');
        self::assertEquals('http://localhost.localdomain/resource', $url);
    }

    public function testLinkCreationWithoutIdCreatesFullyQualifiedLink(): void
    {
        $url = $this->plugin->createLink('resource');
        self::assertEquals('http://localhost.localdomain/resource', $url);
    }

    public function testLinkCreationWithIdCreatesFullyQualifiedLink(): void
    {
        $url = $this->plugin->createLink('resource', 123);
        self::assertEquals('http://localhost.localdomain/resource/123', $url);
    }

    public function testLinkCreationFromEntity(): void
    {
        $self = new Link('self');
        $self->setRoute('resource', ['id' => 123]);
        $docs = new Link('describedby');
        $docs->setRoute('docs');
        $entity = new Entity([], 123);
        $entity->getLinks()->add($self)->add($docs);
        $links = $this->plugin->fromResource($entity);

        self::assertIsArray($links);
        self::assertArrayHasKey('self', $links, var_export($links, 1));
        self::assertArrayHasKey('describedby', $links, var_export($links, 1));

        $selfLink = $links['self'];
        self::assertIsArray($selfLink);
        self::assertArrayHasKey('href', $selfLink);
        self::assertEquals('http://localhost.localdomain/resource/123', $selfLink['href']);

        $docsLink = $links['describedby'];
        self::assertIsArray($docsLink);
        self::assertArrayHasKey('href', $docsLink);
        self::assertEquals('http://localhost.localdomain/help', $docsLink['href']);
    }

    public function testRendersEmbeddedCollectionsInsideEntities(): void
    {
        $collection = new Collection(
            [
                (object) ['id' => 'foo', 'name' => 'foo'],
                (object) ['id' => 'bar', 'name' => 'bar'],
                (object) ['id' => 'baz', 'name' => 'baz'],
            ],
            'hostname/contacts'
        );
        $entity = new Entity(
            (object) [
                'id'       => 'user',
                'contacts' => $collection,
            ],
            'user'
        );
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'user']);
        $entity->getLinks()->add($self);

        $rendered = $this->plugin->renderEntity($entity);
        $this->assertRelationalLinkContains('/users/', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertArrayHasKey('contacts', $embed);
        $contacts = $embed['contacts'];
        self::assertIsArray($contacts);
        self::assertCount(3, $contacts);
        foreach ($contacts as $contact) {
            self::assertIsArray($contact);
            $this->assertRelationalLinkContains('/contacts/', 'self', $contact);
        }
    }

    public function testRendersEmbeddedEntitiesInsideEntitiesBasedOnMetadataMap(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');
        $object->first_child  = new TestAsset\EmbeddedEntity('bar', 'Bar');
        $object->second_child = new TestAsset\EmbeddedEntityWithCustomIdentifier('baz', 'Baz');
        $entity = new Entity($object, 'foo');
        $self = new Link('self');
        $self->setRoute('hostname/resource', ['id' => 'foo']);
        $entity->getLinks()->add($self);

        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\EmbeddedEntity::class => [
                'hydrator' => $this->getObjectPropertyHydratorClass(),
                'route'    => 'hostname/embedded',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\EmbeddedEntityWithCustomIdentifier::class => [
                'hydrator'        => $this->getObjectPropertyHydratorClass(),
                'route'           => 'hostname/embedded_custom',
                'route_identifier_name' => 'custom_id',
                'entity_identifier_name' => 'custom_id',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $rendered = $this->plugin->renderEntity($entity);
        $this->assertRelationalLinkContains('/resource/foo', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertCount(2, $embed);
        self::assertArrayHasKey('first_child', $embed);
        self::assertArrayHasKey('second_child', $embed);

        $first = $embed['first_child'];
        self::assertIsArray($first);
        $this->assertRelationalLinkContains('/embedded/bar', 'self', $first);

        $second = $embed['second_child'];
        self::assertIsArray($second);
        $this->assertRelationalLinkContains('/embedded_custom/baz', 'self', $second);
    }

    public function testMetadataMapLooksForParentClasses(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');
        $object->first_child  = new TestAsset\EmbeddedProxyEntity('bar', 'Bar');
        $object->second_child = new TestAsset\EmbeddedProxyEntityWithCustomIdentifier('baz', 'Baz');
        $entity = new Entity($object, 'foo');
        $self = new Link('self');
        $self->setRoute('hostname/resource', ['id' => 'foo']);
        $entity->getLinks()->add($self);

        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\EmbeddedEntity::class => [
                'hydrator' => $this->getObjectPropertyHydratorClass(),
                'route'    => 'hostname/embedded',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\EmbeddedEntityWithCustomIdentifier::class => [
                'hydrator'        => $this->getObjectPropertyHydratorClass(),
                'route'           => 'hostname/embedded_custom',
                'route_identifier_name' => 'custom_id',
                'entity_identifier_name' => 'custom_id',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $rendered = $this->plugin->renderEntity($entity);
        $this->assertRelationalLinkContains('/resource/foo', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertCount(2, $embed);
        self::assertArrayHasKey('first_child', $embed);
        self::assertArrayHasKey('second_child', $embed);

        $first = $embed['first_child'];
        self::assertIsArray($first);
        $this->assertRelationalLinkContains('/embedded/bar', 'self', $first);

        $second = $embed['second_child'];
        self::assertIsArray($second);
        $this->assertRelationalLinkContains('/embedded_custom/baz', 'self', $second);
    }

    public function testRendersJsonSerializableObjectUsingJsonSerializeMethod(): void
    {
        $object   = new TestAsset\JsonSerializableEntity('foo', 'Foo');
        $entity   = new Entity($object, 'foo');
        $rendered = $this->plugin->renderEntity($entity);

        self::assertArrayHasKey('id', $rendered);
        self::assertArrayNotHasKey('name', $rendered);
        self::assertArrayHasKey('_links', $rendered);
    }

    public function testRendersEmbeddedCollectionsInsideEntitiesBasedOnMetadataMap(): void
    {
        $collection = new TestAsset\Collection([
            (object) ['id' => 'foo', 'name' => 'foo'],
            (object) ['id' => 'bar', 'name' => 'bar'],
            (object) ['id' => 'baz', 'name' => 'baz'],
        ]);

        $metadata = new MetadataMap([
            TestAsset\Collection::class => [
                'is_collection'       => true,
                'collection_name'     => 'collection', // should be overridden
                'route_name'          => 'hostname/contacts',
                'entity_route_name'   => 'hostname/embedded',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $entity = new Entity(
            (object) [
                'id'       => 'user',
                'contacts' => $collection,
            ],
            'user'
        );
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'user']);
        $entity->getLinks()->add($self);

        $rendered = $this->plugin->renderEntity($entity);

        $this->assertRelationalLinkContains('/users/', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertArrayHasKey('contacts', $embed);
        $contacts = $embed['contacts'];
        self::assertIsArray($contacts);
        self::assertCount(3, $contacts);
        foreach ($contacts as $contact) {
            self::assertIsArray($contact);
            self::assertArrayHasKey('id', $contact);
            $this->assertRelationalLinkContains('/embedded/' . $contact['id'], 'self', $contact);
        }
    }

    public function testRendersEmbeddedCollectionsInsideCollectionsBasedOnMetadataMap(): void
    {
        $childCollection = new TestAsset\Collection([
            (object) ['id' => 'foo', 'name' => 'foo'],
            (object) ['id' => 'bar', 'name' => 'bar'],
            (object) ['id' => 'baz', 'name' => 'baz'],
        ]);
        $entity = new TestAsset\Entity('spock', 'Spock');
        $entity->first_child = $childCollection;

        $metadata = new MetadataMap([
            TestAsset\Collection::class => [
                'is_collection'  => true,
                'route'          => 'hostname/contacts',
                'entity_route'   => 'hostname/embedded',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $collection = new Collection([$entity], 'hostname/resource');
        $self = new Link('self');
        $self->setRoute('hostname/resource');
        $collection->getLinks()->add($self);
        $collection->setCollectionName('resources');

        $rendered = $this->plugin->renderCollection($collection);

        $this->assertRelationalLinkContains('/resource', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertArrayHasKey('resources', $embed);
        $resources = $embed['resources'];
        self::assertIsArray($resources);
        self::assertCount(1, $resources);

        $resource = array_shift($resources);
        self::assertIsArray($resource);
        self::assertArrayHasKey('_embedded', $resource);
        self::assertIsArray($resource['_embedded']);
        self::assertArrayHasKey('first_child', $resource['_embedded']);
        self::assertIsArray($resource['_embedded']['first_child']);

        foreach ($resource['_embedded']['first_child'] as $contact) {
            self::assertIsArray($contact);
            self::assertArrayHasKey('id', $contact);
            $this->assertRelationalLinkContains('/embedded/' . $contact['id'], 'self', $contact);
        }
    }

    // @codingStandardsIgnoreStart
    public function testDoesNotRenderEmbeddedEntitiesInsideCollectionsBasedOnMetadataMapAndRenderEmbeddedEntitiesAsFalse(): void
    {
        $entity = new TestAsset\Entity('spock', 'Spock');
        $entity->first_child  = new TestAsset\EmbeddedEntity('bar', 'Bar');
        $entity->second_child = new TestAsset\EmbeddedEntityWithCustomIdentifier('baz', 'Baz');

        $metadata = new MetadataMap([
            TestAsset\EmbeddedEntity::class => [
                'hydrator' => $this->getObjectPropertyHydratorClass(),
                'route'    => 'hostname/embedded',
            ],
            TestAsset\EmbeddedEntityWithCustomIdentifier::class => [
                'hydrator'        => $this->getObjectPropertyHydratorClass(),
                'route'           => 'hostname/embedded_custom',
                'route_identifier_name' => 'custom_id',
                'entity_identifier_name' => 'custom_id',
            ],
            TestAsset\Collection::class => [
                'is_collection'  => true,
                'route'          => 'hostname/contacts',
                'entity_route'   => 'hostname/embedded',
            ],
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);
        $this->plugin->setRenderEmbeddedEntities(false);

        $collection = new Collection([$entity], 'hostname/resource');
        $self = new Link('self');
        $self->setRoute('hostname/resource');
        $collection->getLinks()->add($self);
        $collection->setCollectionName('resources');

        $rendered = $this->plugin->renderCollection($collection);

        $this->assertRelationalLinkContains('/resource', 'self', $rendered);

        self::assertArrayHasKey('_embedded', $rendered);
        $embed = $rendered['_embedded'];
        self::assertArrayHasKey('resources', $embed);
        $resources = $embed['resources'];
        self::assertIsArray($resources);
        self::assertCount(1, $resources);

        $resource = array_shift($resources);
        self::assertIsArray($resource);
        self::assertArrayHasKey('_embedded', $resource);
        self::assertIsArray($resource['_embedded']);

        foreach ($resource['_embedded']['first_child'] as $contact) {
            self::assertIsArray($contact);
            self::assertArrayNotHasKey('id', $contact);
        }
    }
    // @codingStandardsIgnoreEnd

    public function testWillNotAllowInjectingASelfRelationMultipleTimes(): void
    {
        $entity = new Entity([
            'id'  => 1,
            'foo' => 'bar',
        ], 1);
        $links = $entity->getLinks();

        self::assertFalse($links->has('self'));

        $this->plugin->injectSelfLink($entity, 'hostname/resource');

        self::assertTrue($links->has('self'));
        $link = $links->get('self');
        self::assertInstanceOf(Link::class, $link);

        $this->plugin->injectSelfLink($entity, 'hostname/resource');
        self::assertTrue($links->has('self'));
        $link = $links->get('self');
        self::assertInstanceOf(Link::class, $link);
    }

    public function testEntityPropertiesCanBeLinks(): void
    {
        $embeddedLink = new Link('embeddedLink');
        $embeddedLink->setRoute('hostname/contacts', ['id' => 'bar']);

        $properties = [
            'id' => '10',
            'embeddedLink' => $embeddedLink,
        ];

        $entity = new Entity((object) $properties, 'foo');

        $rendered = $this->plugin->renderEntity($entity);

        self::assertArrayHasKey('_links', $rendered);
        self::assertArrayHasKey('embeddedLink', $rendered['_links']);
        self::assertArrayNotHasKey('embeddedLink', $rendered);
        self::assertArrayHasKey('href', $rendered['_links']['embeddedLink']);
        self::assertEquals('http://localhost.localdomain/contacts/bar', $rendered['_links']['embeddedLink']['href']);
    }

    public function testEntityPropertyLinksUseHref(): void
    {
        $link1 = new Link('link1');
        $link1->setUrl('link1');

        $link2 = new Link('link2');
        $link2->setUrl('link2');

        $properties = [
            'id' => '10',
            'bar' => $link1,
            'baz' => $link2,
        ];

        $entity = new Entity((object) $properties, 'foo');

        $rendered = $this->plugin->renderEntity($entity);

        self::assertArrayHasKey('_links', $rendered);
        self::assertArrayHasKey('link1', $rendered['_links']);
        self::assertArrayNotHasKey('bar', $rendered['_links']);
        self::assertArrayNotHasKey('link1', $rendered);

        self::assertArrayHasKey('link2', $rendered['_links']);
        self::assertArrayNotHasKey('baz', $rendered['_links']);
        self::assertArrayNotHasKey('link2', $rendered);
    }

    public function testResourcePropertiesCanBeLinkCollections(): void
    {
        $link = new Link('embeddedLink');
        $link->setRoute('hostname/contacts', ['id' => 'bar']);

        //simple link
        $collection = new LinkCollection();
        $collection->add($link);

        //array of links
        $linkArray = new Link('arrayLink');
        $linkArray->setRoute('hostname/contacts', ['id' => 'bar']);
        $collection->add($linkArray);

        $linkArray = new Link('arrayLink');
        $linkArray->setRoute('hostname/contacts', ['id' => 'baz']);
        $collection->add($linkArray);

        $properties = [
            'id' => '10',
            'links' => $collection,
        ];

        $entity = new Entity((object) $properties, 'foo');

        $rendered = $this->plugin->renderEntity($entity);

        self::assertArrayHasKey('_links', $rendered);
        self::assertArrayHasKey('embeddedLink', $rendered['_links']);
        self::assertArrayNotHasKey('embeddedLink', $rendered);
        self::assertArrayHasKey('href', $rendered['_links']['embeddedLink']);
        self::assertEquals('http://localhost.localdomain/contacts/bar', $rendered['_links']['embeddedLink']['href']);

        self::assertArrayHasKey('arrayLink', $rendered['_links']);
        self::assertCount(2, $rendered['_links']['arrayLink']);
    }

    /**
     * @group 71
     */
    public function testRenderingEmbeddedEntityEmbedsEntity(): void
    {
        $embedded = new Entity((object) ['id' => 'foo', 'name' => 'foo'], 'foo');
        $self = new Link('self');
        $self->setRoute('hostname/contacts', ['id' => 'foo']);
        $embedded->getLinks()->add($self);

        $entity = new Entity((object) ['id' => 'user', 'contact' => $embedded], 'user');
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'user']);
        $entity->getLinks()->add($self);

        $rendered = $this->plugin->renderEntity($entity);

        $this->assertRelationalLinkContains('/users/user', 'self', $rendered);
        self::assertArrayHasKey('_embedded', $rendered);
        self::assertIsArray($rendered['_embedded']);
        self::assertArrayHasKey('contact', $rendered['_embedded']);
        $contact = $rendered['_embedded']['contact'];
        $this->assertRelationalLinkContains('/contacts/foo', 'self', $contact);
    }

    /**
     * @group 71
     */
    public function testRenderingCollectionRendersAllLinksInEmbeddedEntities(): void
    {
        $embedded = new Entity((object) ['id' => 'foo', 'name' => 'foo'], 'foo');
        $links = $embedded->getLinks();
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'foo']);
        $links->add($self);
        $phones = new Link('phones');
        $phones->setUrl('http://localhost.localdomain/users/foo/phones');
        $links->add($phones);

        $collection = new Collection([$embedded]);
        $collection->setCollectionName('users');
        $self = new Link('self');
        $self->setRoute('hostname/users');
        $collection->getLinks()->add($self);

        $rendered = $this->plugin->renderCollection($collection);

        $this->assertRelationalLinkContains('/users', 'self', $rendered);
        self::assertArrayHasKey('_embedded', $rendered);
        self::assertIsArray($rendered['_embedded']);
        self::assertArrayHasKey('users', $rendered['_embedded']);

        $users = $rendered['_embedded']['users'];
        self::assertIsArray($users);
        $user = array_shift($users);

        $this->assertRelationalLinkContains('/users/foo', 'self', $user);
        $this->assertRelationalLinkContains('/users/foo/phones', 'phones', $user);
    }

    public function testEntitiesFromCollectionCanUseHydratorSetInMetadataMap(): void
    {
        $object   = new TestAsset\EntityWithProtectedProperties('foo', 'Foo');
        $entity   = new Entity($object, 'foo');

        $metadata = new MetadataMap([
            TestAsset\EntityWithProtectedProperties::class => [
                'hydrator'   => 'ArraySerializable',
                'route_name' => 'hostname/resource',
            ],
        ]);

        $collection = new Collection([$entity]);
        $collection->setCollectionName('resource');
        $collection->setCollectionRoute('hostname/resource');

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $test = $this->plugin->renderCollection($collection);

        self::assertIsArray($test);
        self::assertArrayHasKey('_embedded', $test);
        self::assertIsArray($test['_embedded']);
        self::assertArrayHasKey('resource', $test['_embedded']);
        self::assertIsArray($test['_embedded']['resource']);

        $resources = $test['_embedded']['resource'];
        $testResource = array_shift($resources);
        self::assertIsArray($testResource);
        self::assertArrayHasKey('id', $testResource);
        self::assertArrayHasKey('name', $testResource);
    }

    /**
     * @group 47
     */
    public function testRetainsLinksInjectedViaMetadataDuringCreateEntity(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');

        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'links'      => [
                    [
                        'rel' => 'describedby',
                        'url' => 'http://example.com/api/help/resource',
                    ],
                    [
                        'rel' => 'children',
                        'route' => [
                            'name' => 'resource/children',
                        ],
                    ],
                ],
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);
        $entity = $this->plugin->createEntity($object, 'hostname/resource', 'id');
        self::assertInstanceOf(Entity::class, $entity);
        $links = $entity->getLinks();
        self::assertTrue($links->has('describedby'), 'Missing describedby link');
        self::assertTrue($links->has('children'), 'Missing children link');

        $describedby = $links->get('describedby');
        self::assertTrue($describedby->hasUrl());
        self::assertEquals('http://example.com/api/help/resource', $describedby->getUrl());

        $children = $links->get('children');
        self::assertTrue($children->hasRoute());
        self::assertEquals('resource/children', $children->getRoute());
    }

    /**
     * @group 79
     */
    public function testRenderEntityTriggersEvents(): void
    {
        $entity = new Entity(
            (object) [
                'id'   => 'user',
                'name' => 'matthew',
            ],
            'user'
        );
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'user']);
        $entity->getLinks()->add($self);

        $this->plugin->getEventManager()->attach('renderEntity', function ($e) {
            $entity = $e->getParam('entity');
            $entity->getLinks()->get('self')->setRouteParams(['id' => 'matthew']);
        });

        $rendered = $this->plugin->renderEntity($entity);
        self::assertStringContainsString('/users/matthew', $rendered['_links']['self']['href']);
    }

    /**
     * @group 79
     */
    public function testRenderCollectionTriggersEvents(): void
    {
        $collection = new Collection(
            [
                (object) ['id' => 'foo', 'name' => 'foo'],
                (object) ['id' => 'bar', 'name' => 'bar'],
                (object) ['id' => 'baz', 'name' => 'baz'],
            ],
            'hostname/contacts'
        );
        $self = new Link('self');
        $self->setRoute('hostname/contacts');
        $collection->getLinks()->add($self);
        $collection->setCollectionName('resources');

        $this->plugin->getEventManager()->attach('renderCollection', function ($e) {
            $collection = $e->getParam('collection');
            $collection->setAttributes(['injected' => true]);
        });

        $rendered = $this->plugin->renderCollection($collection);
        self::assertArrayHasKey('injected', $rendered);
        self::assertTrue($rendered['injected']);

        $this->plugin->getEventManager()->attach('renderCollection.post', function ($e) {
            $collection = $e->getParam('collection');
            $payload = $e->getParam('payload');

            $this->assertInstanceOf(ArrayObject::class, $payload);
            $this->assertInstanceOf(Collection::class, $collection);

            $payload['_post'] = true;
        });

        $rendered = $this->plugin->renderCollection($collection);
        self::assertArrayHasKey('_post', $rendered);
        self::assertTrue($rendered['_post']);
    }

    public function testFromLinkShouldUseLinkExtractor(): void
    {
        $link       = new Link('foo');
        $extraction = [true];

        $linkExtractor = $this->prophesize(LinkExtractor::class);
        $linkExtractor->extract($link)->willReturn($extraction);

        $linkCollectionExtractor = $this->prophesize(LinkCollectionExtractor::class);
        $linkCollectionExtractor->getLinkExtractor()->willReturn($linkExtractor->reveal());

        $this->plugin->setLinkCollectionExtractor($linkCollectionExtractor->reveal());

        $result = $this->plugin->fromLink($link);

        self::assertEquals($extraction, $result);
    }

    public function testFromLinkShouldTriggerPreEvent(): void
    {
        $link       = new Link('foo');
        $extraction = [true];

        $linkExtractor = $this->prophesize(LinkExtractor::class);
        $linkExtractor->extract($link)->willReturn($extraction);

        $linkCollectionExtractor = $this->prophesize(LinkCollectionExtractor::class);
        $linkCollectionExtractor->getLinkExtractor()->willReturn($linkExtractor->reveal());

        $this->plugin->setLinkCollectionExtractor($linkCollectionExtractor->reveal());

        $preEventTriggered = false;

        $this->plugin->getEventManager()->attach(
            'fromLink.pre',
            function (Event $e) use (&$preEventTriggered, $link) {
                $preEventTriggered = true;
                $this->assertSame($link, $e->getParam('linkDefinition'));
            }
        );

        $result = $this->plugin->fromLink($link);

        self::assertEquals($extraction, $result);
        self::assertTrue($preEventTriggered);
    }

    public function testFromLinkCollectionShouldUseLinkCollectionExtractor(): void
    {
        $linkCollection = new LinkCollection();
        $extraction     = [true];

        $linkCollectionExtractor = $this->prophesize(LinkCollectionExtractor::class);
        $linkCollectionExtractor->extract($linkCollection)->willReturn($extraction);

        $this->plugin->setLinkCollectionExtractor($linkCollectionExtractor->reveal());

        $result = $this->plugin->fromLinkCollection($linkCollection);

        self::assertEquals($extraction, $result);
    }

    public function testCreateCollectionShouldUseCollectionRouteMetadataWhenInjectingSelfLink(): void
    {
        $collection = new Collection(['foo' => 'bar']);
        $collection->setCollectionRoute('hostname/resource');
        $collection->setCollectionRouteOptions([
            'query' => [
                'version' => 2,
            ],
        ]);
        $result = $this->plugin->createCollection($collection);
        $links  = $result->getLinks();
        $self   = $links->get('self');
        self::assertEquals([
            'query' => [
                'version' => 2,
            ],
        ], $self->getRouteOptions());
    }

    public function testRenderingCollectionUsesCollectionNameFromMetadataMap(): void
    {
        $object1 = new TestAsset\Entity('foo', 'Foo');
        $object2 = new TestAsset\Entity('bar', 'Bar');
        $object3 = new TestAsset\Entity('baz', 'Baz');

        $collection = new TestAsset\Collection([
            $object1,
            $object2,
            $object3,
        ]);

        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
            TestAsset\Collection::class => [
                'is_collection'       => true,
                'collection_name'     => 'collection',
                'route_name'          => 'hostname/contacts',
                'entity_route_name'   => 'hostname/embedded',
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $halCollection = $this->plugin->createCollection($collection);
        $rendered = $this->plugin->renderCollection($halCollection);

        $this->assertRelationalLinkContains('/contacts', 'self', $rendered);
        self::assertArrayHasKey('_embedded', $rendered);
        self::assertIsArray($rendered['_embedded']);
        self::assertArrayHasKey('collection', $rendered['_embedded']);

        $renderedCollection = $rendered['_embedded']['collection'];

        foreach ($renderedCollection as $entity) {
            $this->assertRelationalLinkContains('/resource/', 'self', $entity);
        }
    }

    /**
     * @group 14
     */
    public function testRenderingPaginatorCollectionRendersPaginationAttributes()
    {
        $set = [];
        for ($id = 1; $id <= 100; ++$id) {
            $entity = new Entity((object) ['id' => $id, 'name' => 'foo'], 'foo');
            $links = $entity->getLinks();
            $self = new Link('self');
            $self->setRoute('hostname/users', ['id' => $id]);
            $links->add($self);
            $set[] = $entity;
        }

        $paginator  = new Paginator(new ArrayPaginator($set));
        $collection = new Collection($paginator);
        $collection->setCollectionName('users');
        $collection->setCollectionRoute('hostname/users');
        $collection->setPage(3);
        $collection->setPageSize(10);

        $rendered = $this->plugin->renderCollection($collection);
        $expected = [
            '_links',
            '_embedded',
            'page_count',
            'page_size',
            'total_items',
            'page',
        ];
        self::assertEquals($expected, array_keys($rendered));
        self::assertEquals(100, $rendered['total_items']);
        self::assertEquals(3, $rendered['page']);
        self::assertEquals(10, $rendered['page_count']);
        self::assertEquals(10, $rendered['page_size']);
        return $rendered;
    }

    /**
     * @group 50
     * @depends testRenderingPaginatorCollectionRendersPaginationAttributes
     */
    public function testRenderingPaginatorCollectionRendersFirstLinkWithoutPageInQueryString($rendered): void
    {
        $links = $rendered['_links'];
        self::assertArrayHasKey('first', $links);
        $first = $links['first'];
        self::assertArrayHasKey('href', $first);
        self::assertStringNotContainsString('page=1', $first['href']);
    }

    /**
     * @group 14
     */
    public function testRenderingNonPaginatorCollectionRendersCountOfTotalItems(): void
    {
        $embedded = new Entity((object) ['id' => 'foo', 'name' => 'foo'], 'foo');
        $links = $embedded->getLinks();
        $self = new Link('self');
        $self->setRoute('hostname/users', ['id' => 'foo']);
        $links->add($self);

        $collection = new Collection([$embedded]);
        $collection->setCollectionName('users');
        $self = new Link('self');
        $self->setRoute('hostname/users');
        $collection->getLinks()->add($self);

        $rendered = $this->plugin->renderCollection($collection);

        $expectedKeys = ['_links', '_embedded', 'total_items'];
        self::assertEquals($expectedKeys, array_keys($rendered));
    }

    /**
     * @group 33
     */
    public function testCreateEntityShouldNotSerializeEntity(): void
    {
        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'   => $this->getObjectPropertyHydratorClass(),
                'route_name' => 'hostname/resource',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
            ],
        ]);
        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $foo = new TestAsset\Entity('foo', 'Foo Bar');

        $entity = $this->plugin->createEntity($foo, 'api.foo', 'foo_id');
        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame($foo, $entity->getEntity());
    }

    /**
     * @group 39
     */
    public function testCreateEntityPassesNullValueForIdentifierIfNotDiscovered(): void
    {
        $entity = ['foo' => 'bar'];
        $hal    = $this->plugin->createEntity($entity, 'api.foo', 'foo_id');
        self::assertInstanceOf(Entity::class, $hal);
        self::assertEquals($entity, $hal->getEntity());
        self::assertNull($hal->getId());

        $links = $hal->getLinks();
        self::assertTrue($links->has('self'));
        $link = $links->get('self');
        $params = $link->getRouteParams();
        self::assertEquals([], $params);
    }

    /**
     * @dataProvider renderEntityMaxDepthProvider
     *
     * @param Entity      $entity
     * @param MetadataMap $metadataMap
     * @param array       $expectedResult
     * @param array       $exception
     */
    public function testRenderEntityMaxDepth($entity, $metadataMap, $expectedResult, $exception = null): void
    {
        $this->plugin->setMetadataMap($metadataMap);

        $metadataMap->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        if ($exception) {
            $this->expectException($exception['class']);
            $this->expectExceptionMessage($exception['message']);
        }

        $result = $this->plugin->renderEntity($entity);

        self::assertEquals($expectedResult, $result);
    }

    public function renderEntityMaxDepthProvider(): array
    {
        return [
            /**
             * [
             *     $entity,
             *     $metadataMap,
             *     $expectedResult,
             *     $exception,
             * ]
             */
            [
                $this->createNestedEntity(),
                $this->createNestedMetadataMap(),
                null,
                [
                    'class'   => CircularReferenceException::class,
                    'message' => 'Circular reference detected in \'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity\'',
                ],
            ],
            [
                $this->createNestedEntity(),
                $this->createNestedMetadataMap(1),
                [
                    'id' => 'foo',
                    'name' => 'Foo',
                    'second_child' => null,
                    '_embedded' => [
                        'first_child' => [
                            'id' => 'bar',
                            '_embedded' => [
                                'parent' => [
                                    '_links' => [
                                        'self' => [
                                            'href' => 'http://localhost.localdomain/resource/foo',
                                        ],
                                    ],
                                ],
                            ],
                            '_links' => [
                                'self' => [
                                    'href' => 'http://localhost.localdomain/embedded/bar',
                                ],
                            ],
                        ],
                    ],
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost.localdomain/resource/foo',
                        ],
                    ],
                ],
            ],
            [
                $this->createNestedEntity(),
                $this->createNestedMetadataMap(2),
                [
                    'id' => 'foo',
                    'name' => 'Foo',
                    'second_child' => null,
                    '_embedded' => [
                        'first_child' => [
                            'id' => 'bar',
                            '_embedded' => [
                                'parent' => [
                                    'id' => 'foo',
                                    'name' => 'Foo',
                                    'second_child' => null,
                                    '_embedded' => [
                                        'first_child' => [
                                            '_links' => [
                                                'self' => [
                                                    'href' => 'http://localhost.localdomain/embedded/bar',
                                                ],
                                            ],
                                        ],
                                    ],
                                    '_links' => [
                                        'self' => [
                                            'href' => 'http://localhost.localdomain/resource/foo',
                                        ],
                                    ],
                                ],
                            ],
                            '_links' => [
                                'self' => [
                                    'href' => 'http://localhost.localdomain/embedded/bar',
                                ],
                            ],
                        ],
                    ],
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost.localdomain/resource/foo',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testSubsequentRenderEntityCalls(): void
    {
        $entity = $this->createNestedEntity();
        $metadataMap1 = $this->createNestedMetadataMap(0);
        $metadataMap2 = $this->createNestedMetadataMap(1);

        $metadataMap1->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));
        $metadataMap2->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadataMap1);
        $result1 = $this->plugin->renderEntity($entity);

        $this->plugin->setMetadataMap($metadataMap2);
        $result2 = $this->plugin->renderEntity($entity);

        self::assertNotEquals($result1, $result2);
    }

    /**
     * @dataProvider renderCollectionWithMaxDepthProvider
     *
     * @param Collection  $collection
     * @param MetadataMap $metadataMap
     * @param array|null  $expectedResult
     * @param array|null  $exception
     */
    public function testRenderCollectionWithMaxDepth(
        $collection,
        $metadataMap,
        $expectedResult,
        $exception = null
    ): void {
        $metadataMap->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));
        $this->plugin->setMetadataMap($metadataMap);

        if ($exception) {
            $this->expectException($exception['class']);
            $this->expectExceptionMessage($exception['message']);
        }

        if (is_callable($collection)) {
            $collection = $collection();
        }

        $halCollection = $this->plugin->createCollection($collection);
        $result = $this->plugin->renderCollection($halCollection);

        self::assertEquals($expectedResult, $result);
    }

    public function renderCollectionWithMaxDepthProvider(): array
    {
        return [
            [
                function () {
                    $object1 = new TestAsset\Entity('foo', 'Foo');
                    $object1->first_child  = new TestAsset\EmbeddedEntityWithBackReference('bar', $object1);
                    $object2 = new TestAsset\Entity('bar', 'Bar');
                    $object3 = new TestAsset\Entity('baz', 'Baz');

                    $collection = new TestAsset\Collection([
                        $object1,
                        $object2,
                        $object3,
                    ]);

                    return $collection;
                },
                $this->createNestedCollectionMetadataMap(),
                null,
                [
                    'class'   => CircularReferenceException::class,
                    'message' => 'Circular reference detected in \'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity\'',
                ],
            ],
            [
                function () {
                    $object1 = new TestAsset\Entity('foo', 'Foo');
                    $object1->first_child  = new TestAsset\EmbeddedEntityWithBackReference('bar', $object1);
                    $object2 = new TestAsset\Entity('bar', 'Bar');
                    $object3 = new TestAsset\Entity('baz', 'Baz');

                    $collection = new TestAsset\Collection([
                        $object1,
                        $object2,
                        $object3,
                    ]);

                    return $collection;
                },
                $this->createNestedCollectionMetadataMap(1),
                [
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost.localdomain/contacts',
                        ],
                    ],
                    '_embedded' => [
                        'collection' => [
                            [
                                'id'           => 'foo',
                                'name'         => 'Foo',
                                'second_child' => null,
                                '_embedded'    => [
                                    'first_child' => [
                                        'id'        => 'bar',
                                        '_embedded' => [
                                            'parent' => [
                                                '_links' => [
                                                    'self' => [
                                                        'href' => 'http://localhost.localdomain/resource/foo',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        '_links'    => [
                                            'self' => [
                                                'href' => 'http://localhost.localdomain/embedded/bar',
                                            ],
                                        ],
                                    ],
                                ],
                                '_links'       => [
                                    'self' => [
                                        'href' => 'http://localhost.localdomain/resource/foo',
                                    ],
                                ],
                            ],
                            [
                                'id'           => 'bar',
                                'name'         => 'Bar',
                                'first_child'  => null,
                                'second_child' => null,
                                '_links'       => [
                                    'self' => [
                                        'href' => 'http://localhost.localdomain/resource/bar',
                                    ],
                                ],
                            ],
                            [
                                'id'           => 'baz',
                                'name'         => 'Baz',
                                'first_child'  => null,
                                'second_child' => null,
                                '_links'       => [
                                    'self' => [
                                        'href' => 'http://localhost.localdomain/resource/baz',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'total_items' => 3,
                ],
            ],
            [
                function () {
                    $object1 = new TestAsset\Entity('foo', 'Foo');
                    $object2 = new TestAsset\Entity('bar', 'Bar');

                    $collection = new TestAsset\Collection([
                        $object1,
                        $object2,
                    ]);
                    $object1->first_child = $collection;

                    return $collection;
                },
                $this->createNestedCollectionMetadataMap(),
                null,
                [
                    'class'   => CircularReferenceException::class,
                    'message' => 'Circular reference detected in \'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity\'',
                ],
            ],
            [
                function () {
                    $object1 = new TestAsset\Entity('foo', 'Foo');
                    $object2 = new TestAsset\Entity('bar', 'Bar');

                    $collection = new TestAsset\Collection([
                        $object1,
                        $object2,
                    ]);
                    $object1->first_child = $collection;

                    return $collection;
                },
                $this->createNestedCollectionMetadataMap(1),
                [
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost.localdomain/contacts',
                        ],
                    ],
                    '_embedded' => [
                        'collection' => [
                            [
                                'id'           => 'foo',
                                'name'         => 'Foo',
                                'second_child' => null,
                                '_embedded'    => [
                                    'first_child' => [
                                        [
                                            '_links' => [
                                                'self' => [
                                                    'href' => 'http://localhost.localdomain/resource/foo',
                                                ],
                                            ],
                                        ],
                                        [
                                            '_links' => [
                                                'self' => [
                                                    'href' => 'http://localhost.localdomain/resource/bar',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                '_links'       => [
                                    'self' => [
                                        'href' => 'http://localhost.localdomain/resource/foo',
                                    ],
                                ],
                            ],
                            [
                                'id'           => 'bar',
                                'name'         => 'Bar',
                                'first_child'  => null,
                                'second_child' => null,
                                '_links'       => [
                                    'self' => [
                                        'href' => 'http://localhost.localdomain/resource/bar',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'total_items' => 2,
                ],
            ],
        ];
    }

    /**
     * @group 102
     */
    public function testRenderingEntityTwiceMustNotDuplicateLinkProperties(): void
    {
        $link = new Link('resource');
        $link->setRoute('resource', ['id' => 'user']);

        $entity = new Entity(
            (object) [
                'id'   => 'user',
                'name' => 'matthew',
                'resource' => $link,
            ],
            'user'
        );

        $rendered1 = $this->plugin->renderEntity($entity);
        $rendered2 = $this->plugin->renderEntity($entity);
        self::assertEquals($rendered1, $rendered2);
    }

    /**
     * @group 102
     */
    public function testRenderingEntityTwiceMustNotDuplicateLinkCollectionProperties(): void
    {
        $link = new Link('resource');
        $link->setRoute('resource', ['id' => 'user']);
        $links = new LinkCollection();
        $links->add($link);

        $entity = new Entity(
            (object) [
                'id'   => 'user',
                'name' => 'matthew',
                'resources' => $links,
            ],
            'user'
        );

        $rendered1 = $this->plugin->renderEntity($entity);
        $rendered2 = $this->plugin->renderEntity($entity);
        self::assertEquals($rendered1, $rendered2);
    }

    public function testCreateEntityFromMetadataWithoutForcedSelfLinks(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');
        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'        => $this->getObjectPropertyHydratorClass(),
                'route_name'      => 'hostname/resource',
                'links'           => [],
                'force_self_link' => false,
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));
        $this->plugin->setMetadataMap($metadata);
        $entity = $this->plugin->createEntityFromMetadata(
            $object,
            $metadata->get(TestAsset\Entity::class)
        );
        $links = $entity->getLinks();
        self::assertFalse($links->has('self'));
    }

    public function testCreateEntityWithoutForcedSelfLinks(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');

        $metadata = new MetadataMap([
            TestAsset\Entity::class => [
                'hydrator'        => $this->getObjectPropertyHydratorClass(),
                'route_name'      => 'hostname/resource',
                'links'           => [],
                'force_self_link' => false,
            ],
        ]);
        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);
        $entity = $this->plugin->createEntity($object, 'hostname/resource', 'id');
        $links = $entity->getLinks();
        self::assertFalse($links->has('self'));
    }

    public function testCreateCollectionFromMetadataWithoutForcedSelfLinks(): void
    {
        $set = new TestAsset\Collection([
            (object) ['id' => 'foo', 'name' => 'foo'],
            (object) ['id' => 'bar', 'name' => 'bar'],
            (object) ['id' => 'baz', 'name' => 'baz'],
        ]);

        $metadata = new MetadataMap([
            TestAsset\Collection::class => [
                'is_collection'     => true,
                'route_name'        => 'hostname/contacts',
                'entity_route_name' => 'hostname/embedded',
                'links'             => [],
                'force_self_link'   => false,
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $collection = $this->plugin->createCollectionFromMetadata(
            $set,
            $metadata->get(TestAsset\Collection::class)
        );
        $links = $collection->getLinks();
        self::assertFalse($links->has('self'));
    }

    public function testCreateCollectionWithoutForcedSelfLinks(): void
    {
        $collection = ['foo' => 'bar'];
        $metadata = new MetadataMap([
            Collection::class => [
                'is_collection'     => true,
                'route_name'        => 'hostname/contacts',
                'entity_route_name' => 'hostname/embedded',
                'links'             => [],
                'force_self_link'   => false,
            ],
        ]);

        $metadata->setHydratorManager(new Hydrator\HydratorPluginManager(new ServiceManager()));

        $this->plugin->setMetadataMap($metadata);

        $result = $this->plugin->createCollection($collection);
        $links  = $result->getLinks();
        self::assertFalse($links->has('self'));
    }

    /**
     * This is a special use-case. See comment in Hal::extractCollection.
     */
    public function testExtractCollectionShouldAddSelfLinkToEntityIfEntityIsArray(): void
    {
        $object = ['id' => 'Foo'];
        $collection = new Collection([$object]);
        $collection->setEntityRoute('hostname/resource');
        $method = new \ReflectionMethod($this->plugin, 'extractCollection');
        $method->setAccessible(true);
        $result = $method->invoke($this->plugin, $collection);
        self::assertTrue(isset($result[0]['_links']['self']));
    }

    public function assertIsEntity($entity): void
    {
        self::assertIsArray($entity);
        self::assertArrayHasKey('_links', $entity, 'Invalid HAL entity; does not contain links');
        self::assertIsArray($entity['_links']);
    }

    public function assertEntityHasRelationalLink($relation, $entity): void
    {
        $this->assertIsEntity($entity);
        $links = $entity['_links'];
        self::assertArrayHasKey(
            $relation,
            $links,
            sprintf('HAL links do not contain relation "%s"', $relation)
        );
        $link = $links[$relation];
        self::assertIsArray($link);
    }

    public function assertRelationalLinkEquals($match, $relation, $entity): void
    {
        $this->assertEntityHasRelationalLink($relation, $entity);
        $link = $entity['_links'][$relation];
        self::assertArrayHasKey(
            'href',
            $link,
            sprintf(
                '%s relational link does not have an href; received %s',
                $relation,
                var_export($link, 1)
            )
        );
        $href = $link['href'];
        self::assertEquals($match, $href);
    }

    public function testRendersEntityWithAssociatedLinks(): void
    {
        $item = new Entity([
            'foo' => 'bar',
            'id'  => 'identifier',
        ], 'identifier');
        $links = $item->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource')->setRouteParams(['id' => 'identifier']);
        $links->add($self);

        $result = $this->plugin->renderEntity($item);

        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/identifier', 'self', $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    public function testCanRenderStdclassEntity(): void
    {
        $item = (object) [
            'foo' => 'bar',
            'id'  => 'identifier',
        ];

        $item  = new Entity($item, 'identifier');
        $links = $item->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource')->setRouteParams(['id' => 'identifier']);
        $links->add($self);

        $result = $this->plugin->renderEntity($item);

        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/identifier', 'self', $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    public function testCanSerializeHydratableEntity(): void
    {
        $hydratorClass = $this->getArraySerializableHydratorClass();
        $this->plugin->addHydrator(
            HalTestAsset\ArraySerializable::class,
            new $hydratorClass()
        );

        $item  = new Entity(new HalTestAsset\ArraySerializable(), 'identifier');
        $links = $item->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource')->setRouteParams(['id' => 'identifier']);
        $links->add($self);

        $result = $this->plugin->renderEntity($item);

        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/identifier', 'self', $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    public function testUsesDefaultHydratorIfAvailable(): void
    {
        $hydratorClass = $this->getArraySerializableHydratorClass();
        $this->plugin->setDefaultHydrator(
            new $hydratorClass()
        );

        $item  = new Entity(new HalTestAsset\ArraySerializable(), 'identifier');
        $links = $item->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource')->setRouteParams(['id' => 'identifier']);
        $links->add($self);

        $result = $this->plugin->renderEntity($item);

        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/identifier', 'self', $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    public function testCanRenderNonPaginatedCollection(): void
    {
        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }

        $collection = new Collection($items);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource', 'self', $result);

        self::assertArrayHasKey('_embedded', $result);
        self::assertIsArray($result['_embedded']);
        self::assertArrayHasKey('items', $result['_embedded']);
        self::assertIsArray($result['_embedded']['items']);
        self::assertCount(100, $result['_embedded']['items']);

        foreach ($result['_embedded']['items'] as $key => $item) {
            $id = $key + 1;

            $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/' . $id, 'self', $item);
            self::assertArrayHasKey('id', $item, \var_export($item, 1));
            self::assertEquals($id, $item['id']);
            self::assertArrayHasKey('foo', $item);
            self::assertEquals('bar', $item['foo']);
        }
    }

    public function testCanRenderPaginatedCollection(): void
    {
        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }
        $adapter   = new ArrayPaginator($items);
        $paginator = new Paginator($adapter);

        $collection = new Collection($paginator);
        $collection->setPageSize(5);
        $collection->setPage(3);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource?page=3', 'self', $result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource', 'first', $result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource?page=20', 'last', $result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource?page=2', 'prev', $result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource?page=4', 'next', $result);

        self::assertArrayHasKey('_embedded', $result);
        self::assertIsArray($result['_embedded']);
        self::assertArrayHasKey('items', $result['_embedded']);
        self::assertIsArray($result['_embedded']['items']);
        self::assertCount(5, $result['_embedded']['items']);

        foreach ($result['_embedded']['items'] as $key => $item) {
            $id = $key + 11;

            $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/' . $id, 'self', $item);
            self::assertArrayHasKey('id', $item, \var_export($item, 1));
            self::assertEquals($id, $item['id']);
            self::assertArrayHasKey('foo', $item);
            self::assertEquals('bar', $item['foo']);
        }
    }

    public function invalidPages(): array
    {
        return [
            '-1'   => [-1],
            '1000' => [1000],
        ];
    }

    /**
     * @dataProvider invalidPages
     *
     * @param int $page
     */
    public function testRenderingPaginatedCollectionCanReturnApiProblemIfPageIsTooHighOrTooLow($page): void
    {
        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }
        $adapter   = new ArrayPaginator($items);
        $paginator = new Paginator($adapter);

        $collection = new Collection($paginator, 'resource');
        $collection->setPageSize(5);

        // Using reflection object so we can force a negative page number if desired
        $r = new ReflectionObject($collection);
        $p = $r->getProperty('page');
        $p->setAccessible(true);
        $p->setValue($collection, $page);

        /* @var ApiProblem */
        $result = $this->plugin->renderCollection($collection);

        self::assertInstanceOf(ApiProblem::class, $result, \var_export($result, 1));

        $data = $result->toArray();
        self::assertArrayHasKey('status', $data, \var_export($result, 1));
        self::assertEquals(409, $data['status']);
        self::assertArrayHasKey('detail', $data);
        self::assertEquals('Invalid page provided', $data['detail']);
    }

    public function testRendersAttributesAsPartOfNonPaginatedCollection(): void
    {
        $attributes = [
            'count' => 100,
            'type'  => 'foo',
        ];

        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }

        $collection = new Collection($items, 'resource');
        $collection->setAttributes($attributes);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);
        self::assertArrayHasKey('count', $result, \var_export($result, 1));
        self::assertEquals(100, $result['count']);
        self::assertArrayHasKey('type', $result);
        self::assertEquals('foo', $result['type']);
    }

    public function testRendersAttributeAsPartOfPaginatedCollection(): void
    {
        $attributes = [
            'count' => 100,
            'type'  => 'foo',
        ];

        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }
        $adapter   = new ArrayPaginator($items);
        $paginator = new Paginator($adapter);

        $collection = new Collection($paginator);
        $collection->setPageSize(5);
        $collection->setPage(3);
        $collection->setAttributes($attributes);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);
        self::assertArrayHasKey('count', $result, \var_export($result, 1));
        self::assertEquals(100, $result['count']);
        self::assertArrayHasKey('type', $result);
        self::assertEquals('foo', $result['type']);
    }

    public function testCanRenderNestedEntitiesAsEmbeddedEntities(): void
    {
        $routeClass   = \class_exists(V2Segment::class) ? V2Segment::class : Segment::class;
        $this->router->addRoute('user', new $routeClass('/user[/:id]'));

        $child = new Entity([
            'id'     => 'matthew',
            'name'   => 'matthew',
            'github' => 'weierophinney',
        ], 'matthew');
        $link = new Link('self');
        $link->setRoute('user')->setRouteParams(['id' => 'matthew']);
        $child->getLinks()->add($link);

        $item = new Entity([
            'foo'  => 'bar',
            'id'   => 'identifier',
            'user' => $child,
        ], 'identifier');
        $link = new Link('self');
        $link->setRoute('resource')->setRouteParams(['id' => 'identifier']);
        $item->getLinks()->add($link);

        $result = $this->plugin->renderEntity($item);

        self::assertIsArray($result);
        self::assertArrayHasKey('_embedded', $result);
        $embedded = $result['_embedded'];
        self::assertArrayHasKey('user', $embedded);
        $user = $embedded['user'];
        $this->assertRelationalLinkContains('/user/matthew', 'self', $user);

        foreach ($child->getEntity() as $key => $value) {
            self::assertArrayHasKey($key, $user);
            self::assertEquals($value, $user[$key]);
        }
    }

    public function testRendersEmbeddedEntitiesOfIndividualNonPaginatedCollections(): void
    {
        $routeClass   = \class_exists(V2Segment::class) ? V2Segment::class : Segment::class;
        $this->router->addRoute('user', new $routeClass('/user[/:id]'));

        $child = new Entity([
            'id'     => 'matthew',
            'name'   => 'matthew',
            'github' => 'weierophinney',
        ], 'matthew');
        $link = new Link('self');
        $link->setRoute('user')->setRouteParams(['id' => 'matthew']);
        $child->getLinks()->add($link);

        $prototype = ['foo' => 'bar', 'user' => $child];
        $items = [];
        foreach (\range(1, 3) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }

        $collection = new Collection($items);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);

        $collection = $result['_embedded']['items'];
        foreach ($collection as $item) {
            self::assertArrayHasKey('_embedded', $item);
            $embedded = $item['_embedded'];
            self::assertArrayHasKey('user', $embedded);

            $user = $embedded['user'];
            $this->assertRelationalLinkContains('/user/matthew', 'self', $user);

            foreach ($child->getEntity() as $key => $value) {
                self::assertArrayHasKey($key, $user);
                self::assertEquals($value, $user[$key]);
            }
        }
    }

    public function testRendersEmbeddedEntitiesOfIndividualPaginatedCollections(): void
    {
        $routeClass   = \class_exists(V2Segment::class) ? V2Segment::class : Segment::class;
        $this->router->addRoute('user', new $routeClass('/user[/:id]'));

        $child = new Entity([
            'id'     => 'matthew',
            'name'   => 'matthew',
            'github' => 'weierophinney',
        ], 'matthew');
        $link = new Link('self');
        $link->setRoute('user')->setRouteParams(['id' => 'matthew']);
        $child->getLinks()->add($link);

        $prototype = ['foo' => 'bar', 'user' => $child];
        $items = [];
        foreach (\range(1, 3) as $id) {
            $item       = $prototype;
            $item['id'] = $id;
            $items[]    = $item;
        }
        $adapter   = new ArrayPaginator($items);
        $paginator = new Paginator($adapter);

        $collection = new Collection($paginator);
        $collection->setPageSize(5);
        $collection->setPage(1);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);
        $collection = $result['_embedded']['items'];
        foreach ($collection as $item) {
            self::assertArrayHasKey('_embedded', $item, \var_export($item, 1));
            $embedded = $item['_embedded'];
            self::assertArrayHasKey('user', $embedded);

            $user = $embedded['user'];
            $this->assertRelationalLinkContains('/user/matthew', 'self', $user);

            foreach ($child->getEntity() as $key => $value) {
                self::assertArrayHasKey($key, $user);
                self::assertEquals($value, $user[$key]);
            }
        }
    }

    public function testAllowsSpecifyingAlternateCallbackForReturningEntityId(): void
    {
        $this->plugin->getEventManager()->attach('getIdFromEntity', function ($e) {
            $entity = $e->getParam('entity');

            if (! \is_array($entity)) {
                return false;
            }

            if (\array_key_exists('name', $entity)) {
                return $entity['name'];
            }

            return false;
        }, 10);

        $prototype = ['foo' => 'bar'];
        $items = [];
        foreach (\range(1, 100) as $id) {
            $item         = $prototype;
            $item['name'] = $id;
            $items[]      = $item;
        }

        $collection = new Collection($items);
        $collection->setCollectionRoute('resource');
        $collection->setEntityRoute('resource');
        $links = $collection->getLinks();
        $self  = new Link('self');
        $self->setRoute('resource');
        $links->add($self);

        $result = $this->plugin->renderCollection($collection);

        self::assertIsArray($result);
        $this->assertRelationalLinkEquals('http://localhost.localdomain/resource', 'self', $result);

        self::assertArrayHasKey('_embedded', $result);
        self::assertIsArray($result['_embedded']);
        self::assertArrayHasKey('items', $result['_embedded']);
        self::assertIsArray($result['_embedded']['items']);
        self::assertCount(100, $result['_embedded']['items']);

        foreach ($result['_embedded']['items'] as $key => $item) {
            $id = $key + 1;

            $this->assertRelationalLinkEquals('http://localhost.localdomain/resource/' . $id, 'self', $item);
            self::assertArrayHasKey('name', $item, \var_export($item, 1));
            self::assertEquals($id, $item['name']);
            self::assertArrayHasKey('foo', $item);
            self::assertEquals('bar', $item['foo']);
        }
    }

    /**
     * @group 100
     */
    public function testRenderEntityPostEventIsTriggered(): void
    {
        $entity = ['id' => 1, 'foo' => 'bar'];
        $halEntity = new Entity($entity, 1);

        $triggered = false;
        $this->plugin->getEventManager()->attach('renderEntity.post', function () use (&$triggered) {
            $triggered = true;
        });

        $this->plugin->renderEntity($halEntity);
        self::assertTrue($triggered);
    }

    /**
     * @group 125
     */
    public function testSetUrlHelperRaisesExceptionIndicatingDeprecation(): void
    {
        $this->expectException(Exception\DeprecatedMethodException::class);
        $this->expectExceptionMessage('can no longer be used to influence URL generation');

        $this->plugin->setUrlHelper(static function () {
        });
    }

    /**
     * @group 125
     */
    public function testSetServerUrlHelperRaisesExceptionIndicatingDeprecation(): void
    {
        $this->expectException(Exception\DeprecatedMethodException::class);
        $this->expectExceptionMessage('can no longer be used to influence URL generation');

        $this->plugin->setServerUrlHelper(static function () {
        });
    }

    /**
     * @group 101
     */
    public function testNotExistingRouteInMetadataLinks(): void
    {
        $object = new TestAsset\Entity('foo', 'Foo');
        $object->first_child  = new TestAsset\EmbeddedEntity('bar', 'Bar');
        $entity = new Entity($object, 'foo');
        $self = new Link('self');
        $self->setRoute('hostname/resource', ['id' => 'foo']);
        $entity->getLinks()->add($self);

        $metadata = new MetadataMap([
            TestAsset\EmbeddedEntity::class => [
                'hydrator' => $this->getObjectPropertyHydratorClass(),
                'route'    => 'hostname/embedded',
                'route_identifier_name' => 'id',
                'entity_identifier_name' => 'id',
                'links' => [
                    'link' => [
                        'rel' => 'link',
                        'route' => [
                            'name' => 'non_existing_route',
                        ],
                    ],
                ],
            ],
        ]);

        $this->plugin->setMetadataMap($metadata);

        $expectedExceptionClass = \class_exists(V2RouterException\RuntimeException::class)
            ? V2RouterException\RuntimeException::class
            : RouterException\RuntimeException::class;

        $this->expectException($expectedExceptionClass);
        $this->plugin->renderEntity($entity);
    }

    protected function createNestedEntity(): Entity
    {
        $object              = new TestAsset\Entity('foo', 'Foo');
        $object->first_child = new TestAsset\EmbeddedEntityWithBackReference('bar', $object);
        $entity              = new Entity($object, 'foo');
        $self                = new Link('self');

        $self->setRoute('hostname/resource', ['id' => 'foo']);
        $entity->getLinks()->add($self);

        return $entity;
    }

    protected function createNestedMetadataMap($maxDepth = null): MetadataMap
    {
        return new MetadataMap(
            [
                TestAsset\Entity::class                          => [
                    'hydrator'               => $this->getObjectPropertyHydratorClass(),
                    'route_name'             => 'hostname/resource',
                    'route_identifier_name'  => 'id',
                    'entity_identifier_name' => 'id',
                    'max_depth'              => $maxDepth,
                ],
                TestAsset\EmbeddedEntityWithBackReference::class => [
                    'hydrator'               => $this->getObjectPropertyHydratorClass(),
                    'route'                  => 'hostname/embedded',
                    'route_identifier_name'  => 'id',
                    'entity_identifier_name' => 'id',
                ],
            ]
        );
    }

    protected function createNestedCollectionMetadataMap($maxDepth = null): MetadataMap
    {
        return new MetadataMap(
            [
                TestAsset\Collection::class                      => [
                    'is_collection'     => true,
                    'collection_name'   => 'collection',
                    'route_name'        => 'hostname/contacts',
                    'entity_route_name' => 'hostname/embedded',
                    'max_depth'         => $maxDepth,
                ],
                TestAsset\Entity::class                          => [
                    'hydrator'               => $this->getObjectPropertyHydratorClass(),
                    'route_name'             => 'hostname/resource',
                    'route_identifier_name'  => 'id',
                    'entity_identifier_name' => 'id',
                ],
                TestAsset\EmbeddedEntityWithBackReference::class => [
                    'hydrator'               => $this->getObjectPropertyHydratorClass(),
                    'route'                  => 'hostname/embedded',
                    'route_identifier_name'  => 'id',
                    'entity_identifier_name' => 'id',
                ],
            ]
        );
    }
}
