<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\View;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Plugin\Hal as HalPlugin;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\View\HelperPluginManager;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function json_decode;

class HalJsonRendererTest extends TestCase
{
    /** @var HalJsonRenderer */
    protected $renderer;

    public function setUp(): void
    {
        $this->renderer = new HalJsonRenderer(new ApiProblemRenderer());
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public function nonHalJsonModels()
    {
        return [
            'view-model'      => [new ViewModel(['name' => 'foo'])],
            'json-view-model' => [new JsonModel(['name' => 'foo'])],
        ];
    }

    /**
     * @dataProvider nonHalJsonModels
     * @param ViewModel $model
     */
    public function testRenderGivenNonHalJsonModelShouldReturnDataInJsonFormat($model): void
    {
        $payload = $this->renderer->render($model);

        self::assertEquals(
            $model->getVariables(),
            json_decode($payload, true)
        );
    }

    public function testRenderGivenHalJsonModelThatContainsHalEntityShouldReturnDataInJsonFormat(): void
    {
        $entity    = [
            'id'   => 123,
            'name' => 'foo',
        ];
        $halEntity = new Entity($entity, 123);
        $model     = new HalJsonModel(['payload' => $halEntity]);

        $helperPluginManager = $this->getHelperPluginManager();

        /** @var MockObject $halPlugin */
        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderEntity')
            ->with($halEntity)
            ->will($this->returnValue($entity));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        self::assertEquals($entity, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelThatContainsHalCollectionShouldReturnDataInJsonFormat(): void
    {
        $collection    = [
            ['id' => 'foo', 'name' => 'foo'],
            ['id' => 'bar', 'name' => 'bar'],
            ['id' => 'baz', 'name' => 'baz'],
        ];
        $halCollection = new Collection($collection);
        $model         = new HalJsonModel(['payload' => $halCollection]);

        $helperPluginManager = $this->getHelperPluginManager();

        /** @var MockObject $halPlugin */
        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($collection));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        self::assertEquals($collection, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelReturningApiProblemShouldReturnApiProblemInJsonFormat(): void
    {
        $halCollection = new Collection([]);
        $model         = new HalJsonModel(['payload' => $halCollection]);

        $apiProblem = new ApiProblem(500, 'error');

        $helperPluginManager = $this->getHelperPluginManager();

        /** @var MockObject $halPlugin */
        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($apiProblem));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $apiProblemData = [
            'type'   => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title'  => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error',
        ];
        self::assertEquals($apiProblemData, json_decode($rendered, true));
    }

    /**
     * @return HelperPluginManager|MockObject
     */
    private function getHelperPluginManager()
    {
        /** @var MockObject|HelperPluginManager $helperPluginManager */
        $helperPluginManager = $this->createMock(HelperPluginManager::class);
        $halPlugin           = $this->createMock(HalPlugin::class);

        $helperPluginManager
            ->method('get')
            ->with('Hal')
            ->will($this->returnValue($halPlugin));

        return $helperPluginManager;
    }
}
