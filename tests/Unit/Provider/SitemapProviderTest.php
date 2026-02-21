<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Provider\SitemapProvider;

final class SitemapProviderTest extends TestCase
{
    public function testProvideDelegatesToCacheManager(): void
    {
        $generator = $this->createMock(SitemapGeneratorInterface::class);
        $cacheManager = $this->createMock(SitemapCacheManagerInterface::class);
        $cacheManager->expects(self::once())
            ->method('get')
            ->with('pages', 1, self::anything())
            ->willReturn('<urlset/>');

        $provider = new SitemapProvider($generator, $cacheManager);

        self::assertSame('<urlset/>', $provider->provide('pages', 1));
    }

    public function testProvideWithNullName(): void
    {
        $generator = $this->createMock(SitemapGeneratorInterface::class);
        $cacheManager = $this->createMock(SitemapCacheManagerInterface::class);
        $cacheManager->expects(self::once())
            ->method('get')
            ->with(null, 1, self::anything())
            ->willReturn('<sitemapindex/>');

        $provider = new SitemapProvider($generator, $cacheManager);

        self::assertSame('<sitemapindex/>', $provider->provide());
    }
}
