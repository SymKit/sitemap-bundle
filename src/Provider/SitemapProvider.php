<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Provider;

use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;

final readonly class SitemapProvider implements SitemapProviderInterface
{
    public function __construct(
        private SitemapGeneratorInterface $generator,
        private SitemapCacheManagerInterface $cacheManager,
    ) {
    }

    public function provide(?string $name = null, int $page = 1): string
    {
        return $this->cacheManager->get($name, $page, function () use ($name, $page) {
            return $this->generator->generate($name, $page);
        });
    }
}
