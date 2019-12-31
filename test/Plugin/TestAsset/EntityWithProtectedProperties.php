<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

use Laminas\Stdlib\ArraySerializableInterface;

class EntityWithProtectedProperties implements ArraySerializableInterface
{
    protected $id;
    protected $name;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    /**
     * Exchange internal values from provided array
     *
     * @param  array $array
     */
    public function exchangeArray(array $array)
    {
        foreach ($array as $key => $value) {
            switch ($key) {
                case 'id':
                    $this->id = $value;
                    break;
                case 'name':
                    $this->name = $value;
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return array(
            'id'   => $this->id,
            'name' => $this->name,
        );
    }
}
