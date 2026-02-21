<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapProviderInterface
{
    public function provide(?string $name = null, int $page = 1): string;
}
