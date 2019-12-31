<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\View;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @subpackage UnitTest
 */
class HalJsonRendererTest extends TestCase
{
    /**
     * @var HalJsonRenderer
     */
    protected $renderer;

    public function setUp()
    {
        $this->renderer = new HalJsonRenderer(new ApiProblemRenderer());
    }

    public function nonHalJsonModels()
    {
        return array(
            'view-model'      => array(new ViewModel(array('name' => 'foo'))),
            'json-view-model' => array(new JsonModel(array('name' => 'foo'))),
        );
    }

    /**
     * @dataProvider nonHalJsonModels
     */
    public function testRenderGivenNonHalJsonModelShouldReturnDataInJsonFormat($model)
    {
        $payload = $this->renderer->render($model);

        $this->assertEquals(
            $model->getVariables(),
            json_decode($payload, true)
        );
    }

    public function testRenderGivenHalJsonModelThatContainsHalEntityShouldReturnDataInJsonFormat()
    {
        $entity = array(
            'id'   => 123,
            'name' => 'foo',
        );
        $halEntity = new Entity($entity, 123);
        $model = new HalJsonModel(array('payload' => $halEntity));

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderEntity')
            ->with($halEntity)
            ->will($this->returnValue($entity));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $this->assertEquals($entity, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelThatContainsHalCollectionShouldReturnDataInJsonFormat()
    {
        $collection = array(
            array('id' => 'foo', 'name' => 'foo'),
            array('id' => 'bar', 'name' => 'bar'),
            array('id' => 'baz', 'name' => 'baz'),
        );
        $halCollection = new Collection($collection);
        $model = new HalJsonModel(array('payload' => $halCollection));

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($collection));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $this->assertEquals($collection, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelReturningApiProblemShouldReturnApiProblemInJsonFormat()
    {
        $halCollection = new Collection(array());
        $model = new HalJsonModel(array('payload' => $halCollection));

        $apiProblem = new ApiProblem(500, 'error');

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($apiProblem));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $apiProblemData = array(
            'type'   => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title'  => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error',
        );
        $this->assertEquals($apiProblemData, json_decode($rendered, true));
    }

    private function getHelperPluginManager()
    {
        $helperPluginManager = $this->getMockBuilder('Laminas\View\HelperPluginManager')
            ->disableOriginalConstructor()
            ->getMock();

        $halPlugin = $this->getMock('Laminas\ApiTools\Hal\Plugin\Hal');

        $helperPluginManager
            ->method('get')
            ->with('Hal')
            ->will($this->returnValue($halPlugin));

        return $helperPluginManager;
    }
}
