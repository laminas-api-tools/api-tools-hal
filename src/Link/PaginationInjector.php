<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\ArrayUtils;

use function count;

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

    private function configureCollection(Collection $halCollection): void
    {
        $collection = $halCollection->getCollection();
        $page       = $halCollection->getPage();
        $pageSize   = $halCollection->getPageSize();

        $collection->setItemCountPerPage($pageSize);
        $collection->setCurrentPageNumber($page);
    }

    private function injectLinks(Collection $halCollection): void
    {
        $this->injectSelfLink($halCollection);
        $this->injectFirstLink($halCollection);
        $this->injectLastLink($halCollection);
        $this->injectPrevLink($halCollection);
        $this->injectNextLink($halCollection);
    }

    private function injectSelfLink(Collection $halCollection): void
    {
        $page = $halCollection->getPage();
        $link = $this->createPaginationLink('self', $halCollection, $page);
        $halCollection->getLinks()->add($link, true);
    }

    private function injectFirstLink(Collection $halCollection): void
    {
        $link = $this->createPaginationLink('first', $halCollection);
        $halCollection->getLinks()->add($link);
    }

    private function injectLastLink(Collection $halCollection): void
    {
        $page = $halCollection->getCollection()->count();
        $link = $this->createPaginationLink('last', $halCollection, $page);
        $halCollection->getLinks()->add($link);
    }

    private function injectPrevLink(Collection $halCollection): void
    {
        $page = $halCollection->getPage();
        $prev = $page > 1 ? $page - 1 : false;

        if ($prev) {
            $link = $this->createPaginationLink('prev', $halCollection, $prev);
            $halCollection->getLinks()->add($link);
        }
    }

    private function injectNextLink(Collection $halCollection): void
    {
        $page      = $halCollection->getPage();
        $pageCount = $halCollection->getCollection()->count();
        $next      = $page < $pageCount ? $page + 1 : false;

        if ($next) {
            $link = $this->createPaginationLink('next', $halCollection, $next);
            $halCollection->getLinks()->add($link);
        }
    }

    /**
     * @param string $relation
     * @param int $page
     * @return Link
     */
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
