<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal;

use Laminas\Stdlib\AbstractOptions;

class RendererOptions extends AbstractOptions
{
    /** @var string */
    protected $defaultHydrator;

    /** @var bool */
    protected $renderEmbeddedEntities = true;

    /** @var bool */
    protected $renderEmbeddedCollections = true;

    /** @var array */
    protected $hydrators = [];

    /**
     * @param string $hydrator
     * @return void
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
     * @return void
     */
    public function setRenderEmbeddedEntities($flag)
    {
        $this->renderEmbeddedEntities = (bool) $flag;
    }

    /**
     * @return bool
     */
    public function getRenderEmbeddedEntities()
    {
        return $this->renderEmbeddedEntities;
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function setRenderEmbeddedCollections($flag)
    {
        $this->renderEmbeddedCollections = (bool) $flag;
    }

    /**
     * @return bool
     */
    public function getRenderEmbeddedCollections()
    {
        return $this->renderEmbeddedCollections;
    }

    /**
     * @param array $hydrators
     * @return void
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
