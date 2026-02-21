<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapCacheInvalidatorInterface
{
    public function onInvalidate(): void;
}
