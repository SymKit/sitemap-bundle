<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Event\SitemapPostGenerateEvent;

final class SitemapPostGenerateEventTest extends TestCase
{
    public function testConstruct(): void
    {
        $event = new SitemapPostGenerateEvent('pages', 1, '<urlset/>');

        self::assertSame('pages', $event->name);
        self::assertSame(1, $event->page);
        self::assertSame('<urlset/>', $event->xml);
    }

    public function testConstructWithNullName(): void
    {
        $event = new SitemapPostGenerateEvent(null, 1, '<sitemapindex/>');

        self::assertNull($event->name);
        self::assertSame('<sitemapindex/>', $event->xml);
    }
}
