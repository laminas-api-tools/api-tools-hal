<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\ArrayUtils;

class PaginationInjector implements PaginationInjectorInterface
{
    /**
     * @inheritDoc
     */
    public function injectPaginationLinks(Collection $halCollection)
    {
        $collection = $halCollection->getCollection();
        if (! $collection instanceof Paginator) {
            return false;
        }

        $this->configureCollection($halCollection);

        $pageCount = count($collection);
        if ($pageCount === 0) {
            return true;
        }

        $page = $halCollection->getPage();

        if ($page < 1 || $page > $pageCount) {
            return new ApiProblem(409, 'Invalid page provided');
        }

        $this->injectLinks($halCollection);

        return true;
    }

    private function configureCollection(Collection $halCollection)
    {
        $collection = $halCollection->getCollection();
        $page       = $halCollection->getPage();
        $pageSize   = $halCollection->getPageSize();

        $collection->setItemCountPerPage($pageSize);
        $collection->setCurrentPageNumber($page);
    }

    private function injectLinks(Collection $halCollection)
    {
        $this->injectSelfLink($halCollection);
        $this->injectFirstLink($halCollection);
        $this->injectLastLink($halCollection);
        $this->injectPrevLink($halCollection);
        $this->injectNextLink($halCollection);
    }

    private function injectSelfLink(Collection $halCollection)
    {
        $page = $halCollection->getPage();
        $link = $this->createPaginationLink('self', $halCollection, $page);
        $halCollection->getLinks()->add($link, true);
    }

    private function injectFirstLink(Collection $halCollection)
    {
        $link = $this->createPaginationLink('first', $halCollection);
        $halCollection->getLinks()->add($link);
    }

    private function injectLastLink(Collection $halCollection)
    {
        $page = $halCollection->getCollection()->count();
        $link = $this->createPaginationLink('last', $halCollection, $page);
        $halCollection->getLinks()->add($link);
    }

    private function injectPrevLink(Collection $halCollection)
    {
        $page = $halCollection->getPage();
        $prev = ($page > 1) ? $page - 1 : false;

        if ($prev) {
            $link = $this->createPaginationLink('prev', $halCollection, $prev);
            $halCollection->getLinks()->add($link);
        }
    }

    private function injectNextLink(Collection $halCollection)
    {
        $page      = $halCollection->getPage();
        $pageCount = $halCollection->getCollection()->count();
        $next      = ($page < $pageCount) ? $page + 1 : false;

        if ($next) {
            $link = $this->createPaginationLink('next', $halCollection, $next);
            $halCollection->getLinks()->add($link);
        }
    }

    private function createPaginationLink($relation, Collection $halCollection, $page = null)
    {
        $options = ArrayUtils::merge(
            $halCollection->getCollectionRouteOptions(),
            ['query' => ['page' => $page]]
        );

        return Link::factory([
            'rel'   => $relation,
            'route' => [
                'name'    => $halCollection->getCollectionRoute(),
                'params'  => $halCollection->getCollectionRouteParams(),
                'options' => $options,
            ],
        ]);
    }
}
