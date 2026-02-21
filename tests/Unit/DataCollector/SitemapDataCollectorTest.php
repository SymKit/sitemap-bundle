<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\DataCollector\SitemapDataCollector;
use Symkit\SitemapBundle\Event\SitemapPostGenerateEvent;
use Symkit\SitemapBundle\Event\SitemapPreGenerateEvent;

final class SitemapDataCollectorTest extends TestCase
{
    public function testCollectGathersLoaderInfo(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(42);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['pages' => $loader]);

        $collector = new SitemapDataCollector($registry, true);
        $collector->collect(new Request(), new Response());

        $loaders = $collector->getLoaders();
        self::assertCount(1, $loaders);
        self::assertArrayHasKey('pages', $loaders);
        self::assertSame(42, $loaders['pages']['count']);
        self::assertSame($loader::class, $loaders['pages']['class']);
        self::assertTrue($collector->isCacheEnabled());
        self::assertSame(42, $collector->getTotalUrls());
    }

    public function testCollectWithCacheDisabled(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn([]);

        $collector = new SitemapDataCollector($registry, false);
        $collector->collect(new Request(), new Response());

        self::assertFalse($collector->isCacheEnabled());
        self::assertSame(0, $collector->getTotalUrls());
    }

    public function testEventTrackingRecordsGeneration(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn([]);

        $collector = new SitemapDataCollector($registry, false);
        $collector->collect(new Request(), new Response());

        $collector->onPreGenerate(new SitemapPreGenerateEvent('pages', 1));
        $collector->onPostGenerate(new SitemapPostGenerateEvent('pages', 1, '<urlset>test</urlset>'));

        $generations = $collector->getGenerations();
        self::assertCount(1, $generations);
        self::assertSame('pages', $generations[0]['name']);
        self::assertSame(1, $generations[0]['page']);
        self::assertGreaterThanOrEqual(0, $generations[0]['duration']);
        self::assertSame(\strlen('<urlset>test</urlset>'), $generations[0]['size']);
    }

    public function testEventTrackingWithNullName(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn([]);

        $collector = new SitemapDataCollector($registry, false);
        $collector->collect(new Request(), new Response());

        $collector->onPreGenerate(new SitemapPreGenerateEvent(null, 1));
        $collector->onPostGenerate(new SitemapPostGenerateEvent(null, 1, '<sitemapindex/>'));

        $generations = $collector->getGenerations();
        self::assertNull($generations[0]['name']);
    }

    public function testPostGenerateWithoutPreGenerateIsIgnored(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn([]);

        $collector = new SitemapDataCollector($registry, false);
        $collector->collect(new Request(), new Response());

        $collector->onPostGenerate(new SitemapPostGenerateEvent('pages', 1, '<urlset/>'));

        self::assertCount(0, $collector->getGenerations());
    }

    public function testGetName(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $collector = new SitemapDataCollector($registry, false);

        self::assertSame('symkit_sitemap', $collector->getName());
    }

    public function testGetTemplate(): void
    {
        self::assertSame(
            '@SymkitSitemap/data_collector/sitemap.html.twig',
            SitemapDataCollector::getTemplate(),
        );
    }

    public function testTotalUrlsWithMultipleLoaders(): void
    {
        $loader1 = $this->createMock(SitemapLoaderInterface::class);
        $loader1->method('count')->willReturn(100);
        $loader2 = $this->createMock(SitemapLoaderInterface::class);
        $loader2->method('count')->willReturn(200);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['a' => $loader1, 'b' => $loader2]);

        $collector = new SitemapDataCollector($registry, false);
        $collector->collect(new Request(), new Response());

        self::assertSame(300, $collector->getTotalUrls());
    }
}
