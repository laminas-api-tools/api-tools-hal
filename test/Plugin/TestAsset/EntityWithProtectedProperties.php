<?php

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

use Laminas\Stdlib\ArraySerializableInterface;

class EntityWithProtectedProperties implements ArraySerializableInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /**
     * @param string $id
     * @param string $name
     */
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
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
