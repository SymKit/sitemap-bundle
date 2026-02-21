<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Model\SitemapAlternate;
use Symkit\SitemapBundle\Model\SitemapNews;
use Symkit\SitemapBundle\Model\SitemapUrl;
use Symkit\SitemapBundle\Model\SitemapVideo;

final class SitemapUrlTest extends TestCase
{
    public function testConstructWithAllParameters(): void
    {
        $lastmod = new DateTimeImmutable('2025-01-01');
        $alternate = new SitemapAlternate('fr', 'https://example.com/fr/page');
        $video = new SitemapVideo('https://example.com/thumb.jpg', 'Video', 'Desc');
        $news = new SitemapNews('Times', 'en', 'Breaking', new DateTimeImmutable());

        $url = new SitemapUrl(
            loc: 'https://example.com/page',
            lastmod: $lastmod,
            changefreq: 'daily',
            priority: '0.8',
            images: [['url' => 'https://example.com/img.jpg']],
            alternates: [$alternate],
            videos: [$video],
            news: $news,
        );

        self::assertSame('https://example.com/page', $url->loc);
        self::assertSame($lastmod, $url->lastmod);
        self::assertSame('daily', $url->changefreq);
        self::assertSame('0.8', $url->priority);
        self::assertCount(1, $url->images);
        self::assertCount(1, $url->alternates);
        self::assertCount(1, $url->videos);
        self::assertSame($news, $url->news);
    }

    public function testConstructWithDefaults(): void
    {
        $url = new SitemapUrl(loc: 'https://example.com');

        self::assertSame('https://example.com', $url->loc);
        self::assertNull($url->lastmod);
        self::assertNull($url->changefreq);
        self::assertNull($url->priority);
        self::assertSame([], $url->images);
        self::assertSame([], $url->alternates);
        self::assertSame([], $url->videos);
        self::assertNull($url->news);
    }
}
