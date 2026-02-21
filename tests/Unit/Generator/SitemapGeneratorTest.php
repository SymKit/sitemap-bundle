<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Generator\SitemapGenerator;
use Symkit\SitemapBundle\Generator\SitemapIndexGenerator;
use Symkit\SitemapBundle\Generator\SitemapUrlGenerator;
use Symkit\SitemapBundle\Model\SitemapUrl;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SitemapGeneratorTest extends TestCase
{
    public function testGenerateWithNullNameDelegatesToIndexGenerator(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(1);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['pages' => $loader]);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap/pages.xml');

        $urlGenerator = new SitemapUrlGenerator($registry, 50000);
        $indexGenerator = new SitemapIndexGenerator($registry, $router, 50000);

        $generator = new SitemapGenerator($urlGenerator, $indexGenerator);
        $result = $generator->generate();

        self::assertStringContainsString('sitemapindex', $result);
    }

    public function testGenerateWithNameDelegatesToUrlGenerator(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(1);
        $loader->method('load')->willReturn([
            new SitemapUrl(loc: 'https://example.com/page'),
        ]);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getLoader')->with('pages')->willReturn($loader);

        $router = $this->createMock(RouterInterface::class);

        $urlGenerator = new SitemapUrlGenerator($registry, 50000);
        $indexGenerator = new SitemapIndexGenerator($registry, $router, 50000);

        $generator = new SitemapGenerator($urlGenerator, $indexGenerator);
        $result = $generator->generate('pages', 1);

        self::assertStringContainsString('https://example.com/page', $result);
    }
}
