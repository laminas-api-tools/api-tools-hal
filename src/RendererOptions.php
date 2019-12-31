<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\Stdlib\AbstractOptions;

class RendererOptions extends AbstractOptions
{
    /**
     * @var string
     */
    protected $defaultHydrator;

    /**
     * @var bool
     */
    protected $renderEmbeddedEntities = true;

    /**
     * @var bool
     */
    protected $renderEmbeddedCollections = true;

    /**
     * @var array
     */
    protected $hydrators = [];

    /**
     * @param string $hydrator
     */
    public function setDefaultHydrator($hydrator)
    {
        $this->defaultHydrator = $hydrator;
    }

    /**
     * @return string
     */
    public function getDefaultHydrator()
    {
        return $this->defaultHydrator;
    }

    /**
     * @param bool $flag
     */
    public function setRenderEmbeddedEntities($flag)
    {
        $this->renderEmbeddedEntities = (bool) $flag;
    }

    /**
     * @return string
     */
    public function getRenderEmbeddedEntities()
    {
        return $this->renderEmbeddedEntities;
    }

    /**
     * @param bool $flag
     */
    public function setRenderEmbeddedCollections($flag)
    {
        $this->renderEmbeddedCollections = (bool) $flag;
    }

    /**
     * @return string
     */
    public function getRenderEmbeddedCollections()
    {
        return $this->renderEmbeddedCollections;
    }

    /**
     * @param array $hydrators
     */
    public function setHydrators(array $hydrators)
    {
        $this->hydrators = $hydrators;
    }

    /**
     * @return array
     */
    public function getHydrators()
    {
        return $this->hydrators;
    }
}
