<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapRegistryInterface
{
    public function getLoader(string $name): SitemapLoaderInterface;

    /**
     * @return array<string, SitemapLoaderInterface>
     */
    public function getAllLoaders(): array;
}
