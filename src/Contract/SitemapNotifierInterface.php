<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapNotifierInterface
{
    public function notify(string $sitemapUrl): void;
}
