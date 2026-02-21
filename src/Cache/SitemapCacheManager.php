<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Cache;

use Closure;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;

final readonly class SitemapCacheManager implements SitemapCacheManagerInterface
{
    public function __construct(
        private ?TagAwareCacheInterface $cache = null,
        private bool $enabled = true,
        private string $tag = 'sitemap',
        private int $ttl = 3600,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function get(?string $name, int $page, Closure $callback): string
    {
        if (!$this->enabled || null === $this->cache) {
            return $callback();
        }

        $key = $this->getCacheKey($name, $page);

        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $key) {
            $item->tag([$this->tag]);
            $item->expiresAfter($this->ttl);

            $this->logger?->debug('Sitemap cache miss.', ['key' => $key]);

            return $callback();
        });
    }

    public function invalidate(): void
    {
        if (!$this->enabled || null === $this->cache) {
            return;
        }

        $this->cache->invalidateTags([$this->tag]);
        $this->logger?->info('Sitemap cache invalidated.', ['tag' => $this->tag]);
    }

    private function getCacheKey(?string $name, int $page): string
    {
        $key = $name ?? 'index';
        if ($name && $page > 1) {
            $key = \sprintf('%s-%d', $key, $page);
        }

        return \sprintf('%s_%s', $this->tag, $key);
    }
}
