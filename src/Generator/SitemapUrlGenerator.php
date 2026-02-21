<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;

final readonly class SitemapUrlGenerator
{
    public function __construct(
        private SitemapRegistryInterface $registry,
        private int $itemsPerPage = 50000,
    ) {
    }

    public function generate(string $name, int $page = 1): string
    {
        $loader = $this->registry->getLoader($name);
        $totalItems = $loader->count();
        $chunks = (int) ceil($totalItems / $this->itemsPerPage);

        if ($page > $chunks || $page < 1) {
            throw SitemapNotFoundException::forName(\sprintf('%s (page %d)', $name, $page));
        }

        $limit = $this->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $builder = new SitemapXmlBuilder();

        return $builder->buildUrlSet($loader->load($limit, $offset));
    }
}
