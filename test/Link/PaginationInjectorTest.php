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
        $this->assertTrue($links->has('self'));
        $this->assertTrue($links->has('first'));
        $this->assertTrue($links->has('last'));
        $this->assertTrue($links->has('prev'));
        $this->assertTrue($links->has('next'));
    }

    public function testInjectPaginationLinksGivenFirstPageShouldInjectLinksExceptForPrevious()
    {
        $halCollection = $this->getHalCollection(5, 1);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        $this->assertTrue($links->has('self'));
        $this->assertTrue($links->has('first'));
        $this->assertTrue($links->has('last'));
        $this->assertFalse($links->has('prev'));
        $this->assertTrue($links->has('next'));
    }

    public function testInjectPaginationLinksGivenLastPageShouldInjectLinksExceptForNext()
    {
        $halCollection = $this->getHalCollection(5, 5);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        $this->assertTrue($links->has('self'));
        $this->assertTrue($links->has('first'));
        $this->assertTrue($links->has('last'));
        $this->assertTrue($links->has('prev'));
        $this->assertFalse($links->has('next'));
    }

    public function testInjectPaginationLinksGivenEmptyCollectionShouldNotInjectAnyLink()
    {
        $halCollection = $this->getHalCollection(0, 1);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $links = $halCollection->getLinks();
        $this->assertFalse($links->has('self'));
        $this->assertFalse($links->has('first'));
        $this->assertFalse($links->has('last'));
        $this->assertFalse($links->has('prev'));
        $this->assertFalse($links->has('next'));
    }

    public function testInjectPaginationLinksGivenPageGreaterThanPageCountShouldReturnApiProblem()
    {
        $halCollection = $this->getHalCollection(5, 6);

        $injector = new PaginationInjector();
        $result = $injector->injectPaginationLinks($halCollection);

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(409, $result->status);
    }

    public function testInjectPaginationLinksGivenCollectionRouteNameShouldInjectLinksWithSameRoute()
    {
        $halCollection = $this->getHalCollection(5, 2);

        $injector = new PaginationInjector();
        $injector->injectPaginationLinks($halCollection);

        $collectionRoute = $halCollection->getCollectionRoute();

        $links = $halCollection->getLinks();
        $this->assertEquals($collectionRoute, $links->get('self')->getRoute());
        $this->assertEquals($collectionRoute, $links->get('first')->getRoute());
        $this->assertEquals($collectionRoute, $links->get('last')->getRoute());
        $this->assertEquals($collectionRoute, $links->get('prev')->getRoute());
        $this->assertEquals($collectionRoute, $links->get('next')->getRoute());
    }
}
