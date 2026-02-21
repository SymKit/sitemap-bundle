<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Event\SitemapPreGenerateEvent;

final class SitemapPreGenerateEventTest extends TestCase
{
    public function testConstructWithName(): void
    {
        $event = new SitemapPreGenerateEvent('pages', 2);

        self::assertSame('pages', $event->name);
        self::assertSame(2, $event->page);
    }

    public function testConstructWithNullName(): void
    {
        $event = new SitemapPreGenerateEvent(null, 1);

        self::assertNull($event->name);
        self::assertSame(1, $event->page);
    }
}
