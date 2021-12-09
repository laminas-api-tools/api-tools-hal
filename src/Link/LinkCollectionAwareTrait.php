<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Link;

trait LinkCollectionAwareTrait
{
    /** @var LinkCollection */
    protected $links;

    /**
     * @return self
     */
    public function setLinks(LinkCollection $links)
    {
        $this->links = $links;
        return $this;
    }

    /**
     * @return LinkCollection
     */
    public function getLinks()
    {
        if (! $this->links instanceof LinkCollection) {
            $this->setLinks(new LinkCollection());
        }
        return $this->links;
    }
}
