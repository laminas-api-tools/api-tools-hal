<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class EmbeddedProxyEntityWithCustomIdentifier extends EmbeddedEntityWithCustomIdentifier
{
    /** @var string */
    public $custom_id;

    /** @var string */
    public $name;

    /**
     * @param string $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->custom_id = $id;
        $this->name      = $name;
    }
}
