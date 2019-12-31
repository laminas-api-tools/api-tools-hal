<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\View;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Resource;
use Laminas\View\Model\JsonModel;

/**
 * Simple extension to facilitate the specialized JsonStrategy and JsonRenderer
 * in this Module.
 */
class HalJsonModel extends JsonModel
{
    /**
     * @var bool
     */
    protected $terminate = true;

    /**
     * Does the payload represent a HAL collection?
     *
     * @return bool
     */
    public function isCollection()
    {
        $payload = $this->getPayload();
        return ($payload instanceof Collection);
    }

    /**
     * Does the payload represent a HAL item?
     *
     * @return bool
     */
    public function isResource()
    {
        $payload = $this->getPayload();
        return ($payload instanceof Resource);
    }

    /**
     * Set the payload for the response
     *
     * This is the value to represent in the response.
     *
     * @param  mixed $payload
     * @return self
     */
    public function setPayload($payload)
    {
        $this->setVariable('payload', $payload);
        return $this;
    }

    /**
     * Retrieve the payload for the response
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->getVariable('payload');
    }
}
