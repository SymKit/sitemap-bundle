<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

use Symkit\SitemapBundle\Model\SitemapUrl;

interface SitemapLoaderInterface
{
    public function count(): int;

    /**
     * @return iterable<SitemapUrl>
     */
    public function load(int $limit, int $offset): iterable;
}
