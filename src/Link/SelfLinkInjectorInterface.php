<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Link;

interface SelfLinkInjectorInterface
{
    /**
     * Inject a "self" relational link based on the route and identifier
     *
     * @param  string $route
     * @param  string $routeIdentifier
     * @return void
     */
    public function injectSelfLink(LinkCollectionAwareInterface $resource, $route, $routeIdentifier = 'id');
}
