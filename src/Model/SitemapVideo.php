<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Model;

final readonly class SitemapVideo
{
    public function __construct(
        public string $thumbnailLoc,
        public string $title,
        public string $description,
        public ?string $contentLoc = null,
        public ?string $playerLoc = null,
        public ?string $duration = null,
    ) {
    }
}
