<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;

use function is_array;

class SelfLinkInjector implements SelfLinkInjectorInterface
{
    /**
     * {@inheritDoc}
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

    /**
     * @param null|array|Entity|Collection $resource
     * @param string|array $route
     * @param string $routeIdentifier
     * @return Link
     */
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
            'rel'   => 'self',
            'route' => $route,
        ];
        return Link::factory($spec);
    }

    /**
     * @param null|array|Entity|Collection $resource
     * @param string $routeIdentifier
     * @return array|string
     * @psalm-return array<empty, empty>|array<array-key, mixed>|string
     */
    private function getRouteParams($resource, $routeIdentifier)
    {
        if ($resource instanceof Collection) {
            return $resource->getCollectionRouteParams();
        }

        $routeParams = [];

        if (
            $resource instanceof Entity
            && null !== $resource->getId()
        ) {
            $routeParams = [
                $routeIdentifier => $resource->getId(),
            ];
        }

        return $routeParams;
    }

    /**
     * @param null|array|Entity|Collection $resource
     * @return array|string
     * @psalm-return array<empty, empty>|array<array-key, mixed>|string
     */
    private function getRouteOptions($resource)
    {
        if ($resource instanceof Collection) {
            return $resource->getCollectionRouteOptions();
        }

        return [];
    }
}
