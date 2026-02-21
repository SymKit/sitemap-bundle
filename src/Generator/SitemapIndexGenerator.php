<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

final readonly class SitemapIndexGenerator
{
    public function __construct(
        private SitemapRegistryInterface $registry,
        private RouterInterface $router,
        private int $itemsPerPage = 50000,
    ) {
    }

    public function generate(): string
    {
        $sitemaps = [];

        foreach ($this->registry->getAllLoaders() as $name => $loader) {
            $totalItems = $loader->count();
            $chunks = (int) ceil($totalItems / $this->itemsPerPage);

            if ($chunks <= 1) {
                $sitemaps[] = [
                    'loc' => $this->router->generate('symkit_sitemap_show', ['name' => $name], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
            } else {
                for ($i = 1; $i <= $chunks; ++$i) {
                    $sitemaps[] = [
                        'loc' => $this->router->generate('symkit_sitemap_show_paginated', [
                            'name' => $name,
                            'page' => $i,
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                    ];
                }
            }
        }

        $builder = new SitemapXmlBuilder();

        return $builder->buildIndex($sitemaps);
    }
}
