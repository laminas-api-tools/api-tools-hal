<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class Entity
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var mixed */
    public $first_child;

    /** @var mixed */
    public $second_child;

    /** @var string */
    protected $doNotExportMe = "some secret data";

    /**
     * @param string $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
