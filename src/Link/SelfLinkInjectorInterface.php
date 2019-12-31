<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Link;

interface SelfLinkInjectorInterface
{
    /**
     * Inject a "self" relational link based on the route and identifier
     *
     * @param  LinkCollectionAwareInterface $resource
     * @param  string $route
     * @param  string $routeIdentifier
     */
    public function injectSelfLink(LinkCollectionAwareInterface $resource, $route, $routeIdentifier = 'id');
}
