<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Model\SitemapUrl;

final class SitemapUrlTest extends TestCase
{
    public function testConstructWithAllParameters(): void
    {
        $lastmod = new DateTimeImmutable('2025-01-01');
        $url = new SitemapUrl(
            loc: 'https://example.com/page',
            lastmod: $lastmod,
            changefreq: 'daily',
            priority: '0.8',
            images: [['url' => 'https://example.com/img.jpg']],
        );

        self::assertSame('https://example.com/page', $url->loc);
        self::assertSame($lastmod, $url->lastmod);
        self::assertSame('daily', $url->changefreq);
        self::assertSame('0.8', $url->priority);
        self::assertCount(1, $url->images);
    }

    public function testConstructWithDefaults(): void
    {
        $url = new SitemapUrl(loc: 'https://example.com');

        self::assertSame('https://example.com', $url->loc);
        self::assertNull($url->lastmod);
        self::assertNull($url->changefreq);
        self::assertNull($url->priority);
        self::assertSame([], $url->images);
    }
}
