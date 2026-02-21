<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapGeneratorInterface
{
    public function generate(?string $name = null, int $page = 1): string;
}
