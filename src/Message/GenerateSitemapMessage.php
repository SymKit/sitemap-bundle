<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Message;

final readonly class GenerateSitemapMessage
{
    public function __construct(
        public ?string $name = null,
        public int $page = 1,
    ) {
    }
}
