<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Generator;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Generator\SitemapXmlBuilder;
use Symkit\SitemapBundle\Model\SitemapAlternate;
use Symkit\SitemapBundle\Model\SitemapNews;
use Symkit\SitemapBundle\Model\SitemapUrl;
use Symkit\SitemapBundle\Model\SitemapVideo;

final class SitemapXmlBuilderTest extends TestCase
{
    public function testBuildUrlSetWithBasicUrl(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(loc: 'https://example.com/page'),
        ]);

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $xml);
        self::assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $xml);
        self::assertStringContainsString('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', $xml);
        self::assertStringContainsString('xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"', $xml);
        self::assertStringContainsString('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"', $xml);
        self::assertStringContainsString('<url><loc>https://example.com/page</loc></url>', $xml);
        self::assertStringNotContainsString('<lastmod>', $xml);
        self::assertStringNotContainsString('<changefreq>', $xml);
        self::assertStringNotContainsString('<priority>', $xml);
    }

    public function testBuildUrlSetEmptyProducesValidXml(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([]);

        self::assertStringContainsString('<urlset', $xml);
        self::assertStringNotContainsString('<url>', $xml);
    }

    public function testBuildUrlSetWithAlternates(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                alternates: [
                    new SitemapAlternate('fr', 'https://example.com/fr/page'),
                    new SitemapAlternate('de', 'https://example.com/de/page'),
                ],
            ),
        ]);

        self::assertStringContainsString('<xhtml:link rel="alternate" hreflang="fr" href="https://example.com/fr/page"/>', $xml);
        self::assertStringContainsString('<xhtml:link rel="alternate" hreflang="de" href="https://example.com/de/page"/>', $xml);
        self::assertSame(2, substr_count($xml, 'xhtml:link'));
    }

    public function testBuildUrlSetWithVideos(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                videos: [
                    new SitemapVideo(
                        thumbnailLoc: 'https://example.com/thumb.jpg',
                        title: 'My Video',
                        description: 'Description',
                        contentLoc: 'https://example.com/video.mp4',
                        playerLoc: 'https://example.com/player',
                        duration: '600',
                    ),
                ],
            ),
        ]);

        self::assertStringContainsString('<video:video>', $xml);
        self::assertStringContainsString('<video:thumbnail_loc>https://example.com/thumb.jpg</video:thumbnail_loc>', $xml);
        self::assertStringContainsString('<video:title>My Video</video:title>', $xml);
        self::assertStringContainsString('<video:description>Description</video:description>', $xml);
        self::assertStringContainsString('<video:content_loc>https://example.com/video.mp4</video:content_loc>', $xml);
        self::assertStringContainsString('<video:player_loc>https://example.com/player</video:player_loc>', $xml);
        self::assertStringContainsString('<video:duration>600</video:duration>', $xml);
        self::assertStringContainsString('</video:video>', $xml);
    }

    public function testBuildUrlSetWithVideoMinimalFields(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                videos: [
                    new SitemapVideo(
                        thumbnailLoc: 'https://example.com/thumb.jpg',
                        title: 'Video',
                        description: 'Desc',
                    ),
                ],
            ),
        ]);

        self::assertStringContainsString('<video:thumbnail_loc>', $xml);
        self::assertStringNotContainsString('<video:content_loc>', $xml);
        self::assertStringNotContainsString('<video:player_loc>', $xml);
        self::assertStringNotContainsString('<video:duration>', $xml);
    }

    public function testBuildUrlSetWithNews(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/article',
                news: new SitemapNews(
                    publicationName: 'Example Times',
                    publicationLanguage: 'en',
                    title: 'Breaking News',
                    publicationDate: new DateTimeImmutable('2025-06-15T10:00:00+00:00'),
                ),
            ),
        ]);

        self::assertStringContainsString('<news:news>', $xml);
        self::assertStringContainsString('<news:publication>', $xml);
        self::assertStringContainsString('<news:name>Example Times</news:name>', $xml);
        self::assertStringContainsString('<news:language>en</news:language>', $xml);
        self::assertStringContainsString('</news:publication>', $xml);
        self::assertStringContainsString('<news:publication_date>2025-06-15T10:00:00+00:00</news:publication_date>', $xml);
        self::assertStringContainsString('<news:title>Breaking News</news:title>', $xml);
        self::assertStringContainsString('</news:news>', $xml);
    }

    public function testBuildUrlSetWithImages(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                images: [
                    ['url' => 'https://example.com/img.jpg', 'title' => 'Image Title', 'caption' => 'Caption'],
                ],
            ),
        ]);

        self::assertStringContainsString('<image:image>', $xml);
        self::assertStringContainsString('<image:loc>https://example.com/img.jpg</image:loc>', $xml);
        self::assertStringContainsString('<image:title>Image Title</image:title>', $xml);
        self::assertStringContainsString('<image:caption>Caption</image:caption>', $xml);
        self::assertStringContainsString('</image:image>', $xml);
    }

    public function testBuildUrlSetWithImageUsingLocKey(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                images: [['loc' => 'https://example.com/img2.jpg']],
            ),
        ]);

        self::assertStringContainsString('<image:loc>https://example.com/img2.jpg</image:loc>', $xml);
    }

    public function testBuildUrlSetWithAllOptionalFields(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(
                loc: 'https://example.com/page',
                lastmod: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
                changefreq: 'daily',
                priority: '0.8',
            ),
        ]);

        self::assertStringContainsString('<lastmod>2025-01-01T00:00:00+00:00</lastmod>', $xml);
        self::assertStringContainsString('<changefreq>daily</changefreq>', $xml);
        self::assertStringContainsString('<priority>0.8</priority>', $xml);
    }

    public function testBuildIndex(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildIndex([
            ['loc' => 'https://example.com/sitemap/pages.xml'],
            ['loc' => 'https://example.com/sitemap/products.xml'],
        ]);

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<sitemap><loc>https://example.com/sitemap/pages.xml</loc></sitemap>', $xml);
        self::assertStringContainsString('<sitemap><loc>https://example.com/sitemap/products.xml</loc></sitemap>', $xml);
        self::assertStringContainsString('</sitemapindex>', $xml);
        self::assertSame(2, substr_count($xml, '<sitemap>'));
    }

    public function testBuildIndexWithLastmod(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildIndex([
            ['loc' => 'https://example.com/sitemap.xml', 'lastmod' => '2025-01-01'],
        ]);

        self::assertStringContainsString('<lastmod>2025-01-01</lastmod>', $xml);
    }

    public function testBuildIndexEmpty(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildIndex([]);

        self::assertStringContainsString('<sitemapindex', $xml);
        self::assertStringNotContainsString('<sitemap>', $xml);
    }

    public function testBuildUrlSetMultipleUrls(): void
    {
        $builder = new SitemapXmlBuilder();
        $xml = $builder->buildUrlSet([
            new SitemapUrl(loc: 'https://example.com/page1'),
            new SitemapUrl(loc: 'https://example.com/page2'),
        ]);

        self::assertSame(2, substr_count($xml, '<url>'));
        self::assertStringContainsString('https://example.com/page1', $xml);
        self::assertStringContainsString('https://example.com/page2', $xml);
    }
}
