<?php

namespace Laminas\ApiTools\Hal\Link;

interface LinkCollectionAwareInterface
{
    /**
     * @return mixed
     */
    public function setLinks(LinkCollection $links);

    /**
     * @return LinkCollection
     */
    public function getLinks();
}
