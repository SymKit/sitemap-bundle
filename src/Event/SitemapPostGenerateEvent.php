<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class SitemapPostGenerateEvent extends Event
{
    public function __construct(
        public readonly ?string $name,
        public readonly int $page,
        public readonly string $xml,
    ) {
    }
}
