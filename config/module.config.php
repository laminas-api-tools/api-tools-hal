<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

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
        'factories' => [
            'Laminas\ApiTools\Hal\HalConfig'       => 'Laminas\ApiTools\Hal\Factory\HalConfigFactory',
            'Laminas\ApiTools\Hal\JsonRenderer'    => 'Laminas\ApiTools\Hal\Factory\HalJsonRendererFactory',
            'Laminas\ApiTools\Hal\JsonStrategy'    => 'Laminas\ApiTools\Hal\Factory\HalJsonStrategyFactory',
            'Laminas\ApiTools\Hal\MetadataMap'     => 'Laminas\ApiTools\Hal\Factory\MetadataMapFactory',
            'Laminas\ApiTools\Hal\RendererOptions' => 'Laminas\ApiTools\Hal\Factory\RendererOptionsFactory',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'Hal' => 'Laminas\ApiTools\Hal\Factory\HalViewHelperFactory',
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'Hal' => 'Laminas\ApiTools\Hal\Factory\HalControllerPluginFactory',
        ],
    ],
];
