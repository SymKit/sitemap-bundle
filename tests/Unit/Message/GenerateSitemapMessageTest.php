<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Message\GenerateSitemapMessage;

final class GenerateSitemapMessageTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $message = new GenerateSitemapMessage();

        self::assertNull($message->name);
        self::assertSame(1, $message->page);
    }

    public function testConstructWithValues(): void
    {
        $message = new GenerateSitemapMessage('pages', 3);

        self::assertSame('pages', $message->name);
        self::assertSame(3, $message->page);
    }
}
