<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symkit\SitemapBundle\Cache\SitemapCacheManager;

final class SitemapCacheManagerTest extends TestCase
{
    public function testGetReturnsCachedValueWhenEnabled(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->willReturn('<xml>cached</xml>');

        $manager = new SitemapCacheManager($cache, true, 'sitemap', 3600);

        $result = $manager->get('pages', 1, fn () => '<xml>fresh</xml>');

        self::assertSame('<xml>cached</xml>', $result);
    }

    public function testGetCallsCallbackWhenDisabled(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::never())->method('get');

        $manager = new SitemapCacheManager($cache, false, 'sitemap', 3600);

        $result = $manager->get('pages', 1, fn () => '<xml>fresh</xml>');

        self::assertSame('<xml>fresh</xml>', $result);
    }

    public function testGetCallsCallbackWhenCacheIsNull(): void
    {
        $manager = new SitemapCacheManager(null, true, 'sitemap', 3600);

        $result = $manager->get('pages', 1, fn () => '<xml>fresh</xml>');

        self::assertSame('<xml>fresh</xml>', $result);
    }

    public function testInvalidateInvalidatesTagsWhenEnabled(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::once())
            ->method('invalidateTags')
            ->with(['my_tag']);

        $manager = new SitemapCacheManager($cache, true, 'my_tag', 3600);
        $manager->invalidate();
    }

    public function testInvalidateDoesNothingWhenDisabled(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::never())->method('invalidateTags');

        $manager = new SitemapCacheManager($cache, false, 'sitemap', 3600);
        $manager->invalidate();
    }

    public function testInvalidateDoesNothingWhenCacheIsNull(): void
    {
        $manager = new SitemapCacheManager(null, true, 'sitemap', 3600);
        $manager->invalidate();

        $this->expectNotToPerformAssertions();
    }

    public function testGetUsesCorrectCacheKeyForIndex(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->with('sitemap_index', self::anything())
            ->willReturn('<xml/>');

        $manager = new SitemapCacheManager($cache, true, 'sitemap', 3600);
        $manager->get(null, 1, fn () => '');
    }

    public function testGetUsesCorrectCacheKeyForNamedSitemap(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->with('sitemap_pages', self::anything())
            ->willReturn('<xml/>');

        $manager = new SitemapCacheManager($cache, true, 'sitemap', 3600);
        $manager->get('pages', 1, fn () => '');
    }

    public function testGetUsesCorrectCacheKeyForPaginatedSitemap(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->with('sitemap_pages-3', self::anything())
            ->willReturn('<xml/>');

        $manager = new SitemapCacheManager($cache, true, 'sitemap', 3600);
        $manager->get('pages', 3, fn () => '');
    }

    public function testInvalidateLogsWhenLoggerPresent(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->method('invalidateTags');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Sitemap cache invalidated.', ['tag' => 'sitemap']);

        $manager = new SitemapCacheManager($cache, true, 'sitemap', 3600, $logger);
        $manager->invalidate();
    }
}
