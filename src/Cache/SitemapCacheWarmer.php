<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

final readonly class SitemapCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private SitemapRegistryInterface $registry,
        private SitemapProviderInterface $provider,
        private int $itemsPerPage,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->provider->provide();

        foreach ($this->registry->getAllLoaders() as $name => $loader) {
            $totalItems = $loader->count();
            $chunks = max(1, (int) ceil($totalItems / $this->itemsPerPage));

            for ($page = 1; $page <= $chunks; ++$page) {
                $this->provider->provide($name, $page);
            }
        }

        return [];
    }
}
