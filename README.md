Laminas HAL
======

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools-hal.png)](https://travis-ci.org/laminas-api-tools/api-tools-hal)

Introduction
------------

This module provides the ability to generate [Hypermedia Application
Language](http://tools.ietf.org/html/draft-kelly-json-hal-06) JSON representations.

Installation
------------

Run the following `composer` command:

```console
$ composer require "laminas-api-tools/api-tools-hal:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "laminas-api-tools/api-tools-hal": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'Laminas\ApiTools\Hal',
    ),
    /* ... */
);
```

Configuration
=============

### User Configuration

This module utilizes the top level key `api-tools-hal` for user configuration.

#### Key: `renderer`

This is a configuration array used to configure the `api-tools-hal` `Hal` view helper/controller plugin.  It
consists of the following keys:

- `default_hydrator` - when present, this named hydrator service will be used as the default
  hydrator by the `Hal` plugin when no hydrator is configured for an entity class.
- `render_embedded_entities` - boolean, default `true`, to render full embedded entities in HAL
  responses; if `false`, embedded entities will contain only their relational links.
- `render_collections` - boolean, default is `true`, to render collections in HAL responses; if
  `false`, only a collection's relational links will be rendered.
- `hydrators` - a map of entity class names to hydrator service names that the `Hal` plugin can use
  when hydrating entities.

#### Key: `metadata_map`

The metadata map is used to hint to the `Hal` plugin how it should render objects of specific
class types. When the `Hal` plugin encounters an object found in the metadata map, it will use the
configuration for that class when creating a representation; this information typically indicates
how to generate relational links, how to serialize the object, and whether or not it represents a
collection.

Each class in the metadata map may contain one or more of the following configuration keys:

- `entity_identifier_name` - name of the class property (after serialization) used for the
  identifier.
- `route_name` - a reference to the route name used to generate `self` relational links for the
  collection or entity.
- `route_identifier_name` - the identifier name used in the route that will represent the entity
  identifier in the URI path. This is often different than the `entity_identifier_name` as each
  variable segment in a route must have a unique name.
- `hydrator` - the hydrator service name to use when serializing an entity.
- `is_collection` - boolean; set to `true` when the class represents a collection.
- `links` - an array of configuration for constructing relational links; see below for the structure
  of links.
- `entity_route_name` - route name for embedded entities of a collection.
- `route_params` - an array of route parameters to use for link generation.
- `route_options` - an array of options to pass to the router during link generation.
- `url` - specific URL to use with this resource, if not using a route.

The `links` property is an array of arrays, each with the following structure:

```php
array(
    'rel'   => 'link relation',
    'url'   => 'string absolute URI to use', // OR
    'route' => array(
        'name'    => 'route name for this link',
        'params'  => array( /* any route params to use for link generation */ ),
        'options' => array( /* any options to pass to the router */ ),
    ),
),
// repeat as needed for any additional relational links
```

### System Configuration

The following configuration is present to ensure the proper functioning of this module in
a Laminas-based application.

```php
// Creates a "HalJson" selector for use with laminas-api-tools/api-tools-content-negotiation
'api-tools-content-negotiation' => array(
    'selectors' => array(
        'HalJson' => array(
            'Laminas\ApiTools\Hal\View\HalJsonModel' => array(
                'application/json',
                'application/*+json',
            ),
        ),
    ),
),
```

Laminas Events
==========

### Events

#### `Laminas\ApiTools\Hal\Plugin\Hal` Event Manager

The `Laminas\ApiTools\Hal\Plugin\Hal` triggers several events during its lifecycle. From the `EventManager`
instance composed into the HAL plugin, you may attach to the following events:

- `renderCollection`
- `renderEntity`
- `createLink`
- `renderCollection.entity`
- `getIdFromEntity`

As an example, you could listen to the `renderEntity` event as follows (the following is done within
a `Module` class for a Laminas module and/or Laminas API Tools API module):

```php
class Module
{
    public function onBootstrap($e)
    {
        $app = $e->getTarget();
        $services = $app->getServiceManager();
        $helpers  = $services->get('ViewHelperManager');
        $hal      = $helpers->get('Hal');

        // The HAL plugin's EventManager instance does not compose a SharedEventManager,
        // so you must attach directly to it.
        $hal->getEventManager()->attach('renderEntity', array($this, 'onRenderEntity'));
    }

    public function onRenderEntity($e)
    {
        $entity = $e->getParam('entity');
        if (! $entity->entity instanceof SomeTypeIHaveDefined) {
            // do nothing
            return;
        }

        // Add a "describedBy" relational link
        $entity->getLinks()->add(\Laminas\ApiTools\Hal\Link\Link::factory(array(
            'rel' => 'describedBy',
            'route' => array(
                'name' => 'my/api/docs',
            ),
        ));
    }
}
```

### Listeners

#### `Laminas\ApiTools\Hal\Module::onRender`

This listener is attached to `MvcEvent::EVENT_RENDER` at priority `100`.  If the controller service
result is a `HalJsonModel`, this listener attaches the `Laminas\ApiTools\Hal\JsonStrategy` to the view at
priority `200`.

Laminas Services
============

### Models

#### `Laminas\ApiTools\Hal\Collection`

`Collection` is responsible for modeling general collections as HAL collections, and composing
relational links.

#### `Laminas\ApiTools\Hal\Entity`

`Entity` is responsible for modeling general purpose entities and plain objects as HAL entities, and
composing relational links.

#### `Laminas\ApiTools\Hal\Link\Link`

`Link` is responsible for modeling a relational link.  The `Link` class also has a static
`factory()` method that can take an array of information as an argument to produce valid `Link`
instances.

#### `Laminas\ApiTools\Hal\Link\LinkCollection`

`LinkCollection` is a model responsible for aggregating a collection of `Link` instances.

#### `Laminas\ApiTools\Hal\Metadata\Metadata`

`Metadata` is responsible for collecting all the necessary dependencies, hydrators and other
information necessary to create HAL entities, links, or collections.

#### `Laminas\ApiTools\Hal\Metadata\MetadataMap`

The `MetadataMap` aggregates an array of class name keyed `Metadata` instances to be used in
producing HAL entities, links, or collections.

### Controller Plugins

#### `Laminas\ApiTools\Hal\Plugin\Hal` (a.k.a. `Hal`)

This class operates both as a view helper and as a controller plugin. It is responsible for
providing controllers the facilities to generate HAL data models, as well as rendering relational
links and HAL data structures.

### View Layer

#### `Laminas\ApiTools\Hal\View\HalJsonModel`

`HalJsonModel` is a view model that when used as the result of a controller service response
signifies to the `api-tools-hal` module that the data within the model should be utilized to
produce a JSON HAL representation.

#### `Laminas\ApiTools\Hal\View\HalJsonRenderer`

`HalJsonRenderer` is a view renderer responsible for rendering `HalJsonModel` instances. In turn,
this renderer will call upon the `Hal` plugin/view helper in order to transform the model content
(an `Entity` or `Collection`) into a HAL representation.

#### `Laminas\ApiTools\Hal\View\HalJsonStrategy`

`HalJsonStrategy` is responsible for selecting `HalJsonRenderer` when it identifies a `HalJsonModel`
as the controller service response.
