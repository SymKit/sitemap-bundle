<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\EventListener;

use Symkit\SitemapBundle\Contract\SitemapCacheInvalidatorInterface;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;

final readonly class SitemapCacheInvalidator implements SitemapCacheInvalidatorInterface
{
    public function __construct(
        private SitemapCacheManagerInterface $cacheManager,
    ) {
    }

    public function onInvalidate(): void
    {
        $this->cacheManager->invalidate();
    }
}
