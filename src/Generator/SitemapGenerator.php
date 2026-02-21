<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;

final readonly class SitemapGenerator implements SitemapGeneratorInterface
{
    public function __construct(
        private SitemapUrlGenerator $urlGenerator,
        private SitemapIndexGenerator $indexGenerator,
    ) {
    }

    public function generate(?string $name = null, int $page = 1): string
    {
        if (null === $name) {
            return $this->indexGenerator->generate();
        }

        return $this->urlGenerator->generate($name, $page);
    }
}
