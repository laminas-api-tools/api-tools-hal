<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Link;

use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;

class LinkUrlBuilder
{
    /**
     * @var ServerUrl
     */
    protected $serverUrlHelper;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @param ServerUrl $serverUrlHelper
     * @param Url $urlHelper
     */
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

        if (substr($path, 0, 4) == 'http') {
            return $path;
        }

        return call_user_func($this->serverUrlHelper, $path);
    }
}
