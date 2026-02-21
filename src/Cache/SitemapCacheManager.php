<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Cache;

use Closure;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class SitemapCacheManager implements SitemapCacheManagerInterface
{
    public function __construct(
        private ?TagAwareCacheInterface $cache = null,
        private bool $enabled = true,
        private string $tag = 'sitemap',
        private int $ttl = 3600,
    ) {
    }

    public function get(?string $name, int $page, Closure $callback): string
    {
        if (!$this->enabled || null === $this->cache) {
            return $callback();
        }

        return $this->cache->get($this->getCacheKey($name, $page), function (ItemInterface $item) use ($callback) {
            $item->tag([$this->tag]);
            $item->expiresAfter($this->ttl);

            return $callback();
        });
    }

    public function invalidate(): void
    {
        if (!$this->enabled || null === $this->cache) {
            return;
        }

        $this->cache->invalidateTags([$this->tag]);
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
