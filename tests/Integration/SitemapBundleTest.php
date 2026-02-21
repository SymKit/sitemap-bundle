<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Controller\SitemapController;
use Symkit\SitemapBundle\SitemapBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class SitemapBundleTest extends TestCase
{
    public function testBundleBootsWithDefaultConfig(): void
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        self::assertTrue($container->has(SitemapController::class), 'Controller should be public');

        $testContainer = $container->get('test.service_container');
        self::assertTrue($testContainer->has(SitemapRegistryInterface::class));
        self::assertTrue($testContainer->has(SitemapGeneratorInterface::class));
        self::assertTrue($testContainer->has(SitemapCacheManagerInterface::class));
        self::assertTrue($testContainer->has(SitemapProviderInterface::class));

        $kernel->shutdown();
    }
}

/**
 * @internal
 */
final class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new SitemapBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
                'router' => [
                    'utf8' => true,
                    'resource' => '.',
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/symkit_sitemap_bundle_test/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/symkit_sitemap_bundle_test/log';
    }
}
