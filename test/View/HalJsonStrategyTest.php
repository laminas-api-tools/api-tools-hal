<?php

namespace LaminasTest\ApiTools\Hal\View;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\View\ApiProblemModel;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\Http\Response;
use Laminas\View\ViewEvent;
use PHPUnit\Framework\TestCase;

class HalJsonStrategyTest extends TestCase
{
    /** @var ViewEvent */
    protected $event;

    /** @var Response */
    protected $response;

    /** @var HalJsonRenderer */
    protected $renderer;

    /** @var HalJsonStrategy */
    protected $strategy;

    public function setUp(): void
    {
        $this->response = new Response();
        $this->event    = new ViewEvent();
        $this->event->setResponse($this->response);

        $this->renderer = new HalJsonRenderer(new ApiProblemRenderer());
        $this->strategy = new HalJsonStrategy($this->renderer);
    }

    public function testSelectRendererReturnsNullIfModelIsNotAHalJsonModel(): void
    {
        self::assertNull($this->strategy->selectRenderer($this->event));
    }

    public function testSelectRendererReturnsRendererIfModelIsAHalJsonModel(): void
    {
        $model = new HalJsonModel();
        $this->event->setModel($model);
        self::assertSame($this->renderer, $this->strategy->selectRenderer($this->event));
    }

    public function testInjectResponseDoesNotSetContentTypeHeaderIfRendererDoesNotMatch(): void
    {
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        self::assertFalse($headers->has('Content-Type'));
    }

    public function testInjectResponseDoesNotSetContentTypeHeaderIfResultIsNotString(): void
    {
        $this->event->setRenderer($this->renderer);
        $this->event->setResult(['foo']);
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        self::assertFalse($headers->has('Content-Type'));
    }

    public function testInjectResponseSetsContentTypeHeaderToDefaultIfNotHalModel(): void
    {
        $this->event->setRenderer($this->renderer);
        $this->event->setResult('{"foo":"bar"}');
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        self::assertTrue($headers->has('Content-Type'));
        $header = $headers->get('Content-Type');
        self::assertEquals('application/json', $header->getFieldValue());
    }

    /**
     * @return array
     */
    public function halObjects()
    {
        $entity = new Entity([
            'foo' => 'bar',
        ], 'identifier', 'route');
        $link   = new Link('self');
        $link->setRoute('resource/route')->setRouteParams(['id' => 'identifier']);
        $entity->getLinks()->add($link);

        $collection = new Collection([$entity]);
        $collection->setCollectionRoute('collection/route');
        $collection->setEntityRoute('resource/route');

        return [
            'entity'     => [$entity],
            'collection' => [$collection],
        ];
    }

    /**
     * @dataProvider halObjects
     * @param array $hal
     */
    public function testInjectResponseSetsContentTypeHeaderToHalForHalModel($hal): void
    {
        $model = new HalJsonModel(['payload' => $hal]);

        $this->event->setModel($model);
        $this->event->setRenderer($this->renderer);
        $this->event->setResult('{"foo":"bar"}');
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        self::assertTrue($headers->has('Content-Type'));
        $header = $headers->get('Content-Type');
        self::assertEquals('application/hal+json', $header->getFieldValue());
    }

    public function testInjectResponseSetsContentTypeHeaderToApiProblemForApiProblemModel(): void
    {
        $problem = new ApiProblem(500, "Error message");
        $model   = new ApiProblemModel($problem);

        $this->event->setModel($model);
        $this->event->setRenderer($this->renderer);
        $this->event->setResult('{"foo":"bar"}');
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        self::assertTrue($headers->has('Content-Type'));
        $header = $headers->get('Content-Type');
        self::assertEquals('application/problem+json', $header->getFieldValue());
    }
}
