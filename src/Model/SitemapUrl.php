<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Model;

use DateTimeInterface;

final readonly class SitemapUrl
{
    /**
     * @param list<array<string, string>> $images
     */
    public function __construct(
        public string $loc,
        public ?DateTimeInterface $lastmod = null,
        public ?string $changefreq = null,
        public ?string $priority = null,
        public array $images = [],
    ) {
    }
}
