<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use DateTimeInterface;
use Rumenx\Sitemap\Sitemap;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

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

        $sitemap = new Sitemap();
        $limit = $this->itemsPerPage;
        $offset = ($page - 1) * $limit;

        foreach ($loader->load($limit, $offset) as $url) {
            $sitemap->add(
                $url->loc,
                $url->lastmod?->format(DateTimeInterface::ATOM),
                $url->priority,
                $url->changefreq,
                $url->images,
            );
        }

        return $sitemap->generate('xml');
    }
}
