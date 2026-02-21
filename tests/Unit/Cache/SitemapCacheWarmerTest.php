<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Cache\SitemapCacheWarmer;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

final class SitemapCacheWarmerTest extends TestCase
{
    public function testIsOptionalReturnsTrue(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $provider = $this->createMock(SitemapProviderInterface::class);

        $warmer = new SitemapCacheWarmer($registry, $provider, 25000);

        self::assertTrue($warmer->isOptional());
    }

    public function testWarmUpGeneratesIndexAndAllPages(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(100);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['pages' => $loader]);

        $calls = [];
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::exactly(2))
            ->method('provide')
            ->willReturnCallback(function (?string $name = null, int $page = 1) use (&$calls) {
                $calls[] = [$name, $page];

                return '<xml/>';
            });

        $warmer = new SitemapCacheWarmer($registry, $provider, 25000);

        self::assertSame([], $warmer->warmUp('/tmp'));
        self::assertSame([[null, 1], ['pages', 1]], $calls);
    }

    public function testWarmUpPaginatesLargeLoaders(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(60000);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['products' => $loader]);

        $calls = [];
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::exactly(4))
            ->method('provide')
            ->willReturnCallback(function (?string $name = null, int $page = 1) use (&$calls) {
                $calls[] = [$name, $page];

                return '<xml/>';
            });

        $warmer = new SitemapCacheWarmer($registry, $provider, 25000);
        $warmer->warmUp('/tmp');

        self::assertSame([
            [null, 1],
            ['products', 1],
            ['products', 2],
            ['products', 3],
        ], $calls);
    }
}
