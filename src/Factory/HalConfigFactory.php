<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use ArrayAccess;
// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;

// phpcs:enable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid

use function is_array;

class HalConfigFactory
{
    /**
     * @return array|ArrayAccess
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var array<string,mixed> $config */
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        return isset($config['api-tools-hal']) && is_array($config['api-tools-hal'])
            ? $config['api-tools-hal']
            : [];
    }
}
