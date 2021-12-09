<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Link;

use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;

use function call_user_func;
use function substr;

class LinkUrlBuilder
{
    /** @var ServerUrl */
    protected $serverUrlHelper;

    /** @var Url */
    protected $urlHelper;

    public function __construct(ServerUrl $serverUrlHelper, Url $urlHelper)
    {
        $this->serverUrlHelper = $serverUrlHelper;
        $this->urlHelper       = $urlHelper;
    }

    /**
     * @param  string $route
     * @param  array $params
     * @param  array $options
     * @param  bool $reUseMatchedParams
     * @return string
     */
    public function buildLinkUrl($route, $params = [], $options = [], $reUseMatchedParams = false)
    {
        $path = call_user_func(
            $this->urlHelper,
            $route,
            $params,
            $options,
            $reUseMatchedParams
        );

        if (substr($path, 0, 4) === 'http') {
            return $path;
        }

        return call_user_func($this->serverUrlHelper, $path);
    }
}
