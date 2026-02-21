<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Model;

final readonly class SitemapAlternate
{
    public function __construct(
        public string $hreflang,
        public string $href,
    ) {
    }
}
