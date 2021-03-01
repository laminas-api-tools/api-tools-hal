<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Link;

use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit\Framework\TestCase;

class LinkCollectionTest extends TestCase
{
    /**
     * @var LinkCollection
     */
    protected $links;

    public function setUp(): void
    {
        $this->links = new LinkCollection();
    }

    public function testCanAddDiscreteLinkRelations()
    {
        $describedby = new Link('describedby');
        $self = new Link('self');
        $this->links->add($describedby);
        $this->links->add($self);

        self::assertTrue($this->links->has('describedby'));
        self::assertSame($describedby, $this->links->get('describedby'));
        self::assertTrue($this->links->has('self'));
        self::assertSame($self, $this->links->get('self'));
    }

    public function testCanAddDuplicateLinkRelations()
    {
        $order1 = new Link('order');
        $order2 = new Link('order');

        $this->links->add($order1)
                    ->add($order2);

        self::assertTrue($this->links->has('order'));
        $orders = $this->links->get('order');
        self::assertIsArray($orders);
        self::assertContains($order1, $orders);
        self::assertContains($order2, $orders);
    }

    public function testCanRemoveLinkRelations()
    {
        $describedby = new Link('describedby');
        $this->links->add($describedby);
        self::assertTrue($this->links->has('describedby'));
        $this->links->remove('describedby');
        self::assertFalse($this->links->has('describedby'));
    }

    public function testCanOverwriteLinkRelations()
    {
        $order1 = new Link('order');
        $order2 = new Link('order');

        $this->links->add($order1)
                    ->add($order2, true);

        self::assertTrue($this->links->has('order'));
        $orders = $this->links->get('order');
        self::assertSame($order2, $orders);
    }

    public function testCanIterateLinks()
    {
        $describedby = new Link('describedby');
        $self = new Link('self');
        $this->links->add($describedby);
        $this->links->add($self);

        self::assertEquals(2, $this->links->count());
        $i = 0;
        foreach ($this->links as $link) {
            self::assertInstanceOf(Link::class, $link);
            ++$i;
        }
        self::assertEquals(2, $i);
    }

    public function testCannotDuplicateSelf()
    {
        $first = new Link('self');
        $second = new Link('self');

        $this->links->add($first)
                    ->add($second);

        self::assertTrue($this->links->has('self'));
        self::assertInstanceOf(Link::class, $this->links->get('self'));
        self::assertSame($second, $this->links->get('self'));
    }
}
