<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;

class SelfLinkInjector implements SelfLinkInjectorInterface
{
    /**
     * @inheritDoc
     */
    public function injectSelfLink(LinkCollectionAwareInterface $resource, $route, $routeIdentifier = 'id')
    {
        $links = $resource->getLinks();
        if ($links->has('self')) {
            return;
        }

        $selfLink = $this->createSelfLink($resource, $route, $routeIdentifier);

        $links->add($selfLink, true);
    }

    private function createSelfLink($resource, $route, $routeIdentifier)
    {
        // build route
        if (! is_array($route)) {
            $route = ['name' => (string) $route];
        }
        $routeParams = $this->getRouteParams($resource, $routeIdentifier);
        if (! empty($routeParams)) {
            $route['params'] = $routeParams;
        }
        $routeOptions = $this->getRouteOptions($resource);
        if (! empty($routeOptions)) {
            $route['options'] = $routeOptions;
        }

        // build link
        $spec = [
            'rel' => 'self',
            'route' => $route,
        ];
        $link = Link::factory($spec);

        return $link;
    }

    private function getRouteParams($resource, $routeIdentifier)
    {
        if ($resource instanceof Collection) {
            return $resource->getCollectionRouteParams();
        }

        $routeParams = [];

        if ($resource instanceof Entity
            && null !== $resource->getId()
        ) {
            $routeParams = [
                $routeIdentifier => $resource->getId(),
            ];
        }

        return $routeParams;
    }

    private function getRouteOptions($resource)
    {
        if ($resource instanceof Collection) {
            return $resource->getCollectionRouteOptions();
        }

        return [];
    }
}
