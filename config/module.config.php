<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

return [
    'api-tools-hal' => [
        'renderer' => [
            // 'default_hydrator' => 'Hydrator Service Name',
            // 'hydrators'        => [
            //     class to hydrate/hydrator service name pairs
            // ],
        ],
        'metadata_map' => [
            // 'Class Name' => [
            //     'hydrator'        => 'Hydrator Service Name, if a resource',
            //     'entity_identifier_name' => 'identifying field name, if a resource',
            //     'route_name'      => 'name of route for this resource',
            //     'is_collection'   => 'boolean; set to true for collections',
            //     'links'           => [
            //         [
            //             'rel'   => 'link relation',
            //             'url'   => 'string absolute URI to use', // OR
            //             'route' => [
            //                 'name'    => 'route name for this link',
            //                 'params'  => [ /* any route params to use for link generation */ ],
            //                 'options' => [ /* any options to pass to the router */ ],
            //             ],
            //         ],
            //         repeat as needed for any additional relational links you want for this resource
            //     ],
            //     'resource_route_name' => 'route name for embedded resources of a collection',
            //     'route_params'        => [ /* any route params to use for link generation */ ],
            //     'route_options'       => [ /* any options to pass to the router */ ],
            //     'url'                 => 'specific URL to use with this resource, if not using a route',
            // ],
            // repeat as needed for each resource/collection type
        ],
        'options' => [
            // Needed for generate valid _link url when you use a proxy
            'use_proxy' => false,
        ],
    ],
    // Creates a "HalJson" selector for laminas-api-tools/api-tools-content-negotiation
    'api-tools-content-negotiation' => [
        'selectors' => [
            'HalJson' => [
                'Laminas\ApiTools\Hal\View\HalJsonModel' => [
                    'application/json',
                    'application/*+json',
                ],
            ],
        ],
    ],
    'service_manager' => [
        // Legacy Zend Framework aliases
        'aliases' => [
            \ZF\Hal\Extractor\LinkExtractor::class => Extractor\LinkExtractor::class,
            \ZF\Hal\Extractor\LinkCollectionExtractor::class => Extractor\LinkCollectionExtractor::class,
            \ZF\Hal\HalConfig::class => HalConfig::class,
            \ZF\Hal\JsonRenderer::class => JsonRenderer::class,
            \ZF\Hal\JsonStrategy::class => JsonStrategy::class,
            \ZF\Hal\Link\LinkUrlBuilder::class => Link\LinkUrlBuilder::class,
            \ZF\Hal\MetadataMap::class => MetadataMap::class,
            \ZF\Hal\RendererOptions::class => RendererOptions::class,
        ],
        'factories' => [
            Extractor\LinkExtractor::class => Factory\LinkExtractorFactory::class,
            Extractor\LinkCollectionExtractor::class => Factory\LinkCollectionExtractorFactory::class,
            HalConfig::class           => Factory\HalConfigFactory::class,
            JsonRenderer::class        => Factory\HalJsonRendererFactory::class,
            JsonStrategy::class        => Factory\HalJsonStrategyFactory::class,
            Link\LinkUrlBuilder::class => Factory\LinkUrlBuilderFactory::class,
            MetadataMap::class         => Factory\MetadataMapFactory::class,
            RendererOptions::class     => Factory\RendererOptionsFactory::class,
        ],
    ],
    'view_helpers' => [
        // Legacy Zend Framework aliases
        'aliases' => [
        ],
        'factories' => [
            'Hal' => Factory\HalViewHelperFactory::class,
        ],
    ],
    'controller_plugins' => [
        // Legacy Zend Framework aliases
        'aliases' => [
        ],
        'factories' => [
            'Hal' => Factory\HalControllerPluginFactory::class,
        ],
    ],
];
