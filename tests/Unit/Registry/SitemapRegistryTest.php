<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;
use Symkit\SitemapBundle\Registry\SitemapRegistry;

final class SitemapRegistryTest extends TestCase
{
    public function testGetLoaderReturnsLoaderByName(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $registry = new SitemapRegistry(new \ArrayIterator(['pages' => $loader]));

        self::assertSame($loader, $registry->getLoader('pages'));
    }

    public function testGetLoaderThrowsOnMissing(): void
    {
        $registry = new SitemapRegistry(new \ArrayIterator([]));

        $this->expectException(SitemapNotFoundException::class);
        $this->expectExceptionMessage('Sitemap loader "unknown" not found.');

        $registry->getLoader('unknown');
    }

    public function testGetAllLoadersReturnsAllRegistered(): void
    {
        $loader1 = $this->createMock(SitemapLoaderInterface::class);
        $loader2 = $this->createMock(SitemapLoaderInterface::class);

        $registry = new SitemapRegistry(new \ArrayIterator([
            'pages' => $loader1,
            'products' => $loader2,
        ]));

        $all = $registry->getAllLoaders();

        self::assertCount(2, $all);
        self::assertSame($loader1, $all['pages']);
        self::assertSame($loader2, $all['products']);
    }
}
