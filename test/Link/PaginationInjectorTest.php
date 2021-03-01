<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Link\PaginationInjector;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;

class PaginationInjectorTest extends TestCase
{
    /**
     * @param  int $pages
     * @param  int $currentPage
     * @return Collection
     */
    private function getHalCollection($pages, $currentPage)
    {
        $items = [];
        for ($i = 0; $i < $pages; $i++) {
            $items[] = [];
        }

        $adapter       = new ArrayAdapter($items);
        $collection    = new Paginator($adapter);
        $halCollection = new Collection($collection);

        $halCollection->setCollectionRoute('foo');
        $halCollection->setPage($currentPage);
        $halCollection->setPageSize(1);

        return $halCollection;
    }

    public function testInjectPaginationLinksGivenIntermediatePageShouldInjectAllLinks()
    {
        $halCollection = $this->getHalCollection(5, 2);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        self::assertTrue($links->has('self'));
        self::assertTrue($links->has('first'));
        self::assertTrue($links->has('last'));
        self::assertTrue($links->has('prev'));
        self::assertTrue($links->has('next'));
    }

    public function testInjectPaginationLinksGivenFirstPageShouldInjectLinksExceptForPrevious()
    {
        $halCollection = $this->getHalCollection(5, 1);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        self::assertTrue($links->has('self'));
        self::assertTrue($links->has('first'));
        self::assertTrue($links->has('last'));
        self::assertFalse($links->has('prev'));
        self::assertTrue($links->has('next'));
    }

    public function testInjectPaginationLinksGivenLastPageShouldInjectLinksExceptForNext()
    {
        $halCollection = $this->getHalCollection(5, 5);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        self::assertTrue($links->has('self'));
        self::assertTrue($links->has('first'));
        self::assertTrue($links->has('last'));
        self::assertTrue($links->has('prev'));
        self::assertFalse($links->has('next'));
    }

    public function testInjectPaginationLinksGivenEmptyCollectionShouldNotInjectAnyLink()
    {
        $halCollection = $this->getHalCollection(0, 1);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        self::assertFalse($links->has('self'));
        self::assertFalse($links->has('first'));
        self::assertFalse($links->has('last'));
        self::assertFalse($links->has('prev'));
        self::assertFalse($links->has('next'));
    }

    public function testInjectPaginationLinksGivenPageGreaterThanPageCountShouldReturnApiProblem()
    {
        $halCollection = $this->getHalCollection(5, 6);

        $injector = new PaginationInjector();
        $result = $injector->injectPaginationLinks($halCollection);

        self::assertInstanceOf(ApiProblem::class, $result);
        self::assertEquals(409, $result->status);
    }

    public function testInjectPaginationLinksGivenCollectionRouteNameShouldInjectLinksWithSameRoute()
    {
        $halCollection = $this->getHalCollection(5, 2);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $collectionRoute = $halCollection->getCollectionRoute();

        $links = $halCollection->getLinks();
        self::assertEquals($collectionRoute, $links->get('self')->getRoute());
        self::assertEquals($collectionRoute, $links->get('first')->getRoute());
        self::assertEquals($collectionRoute, $links->get('last')->getRoute());
        self::assertEquals($collectionRoute, $links->get('prev')->getRoute());
        self::assertEquals($collectionRoute, $links->get('next')->getRoute());
    }
}
