<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

use Closure;

interface SitemapCacheManagerInterface
{
    public function get(?string $name, int $page, Closure $callback): string;

    public function invalidate(): void;
}
