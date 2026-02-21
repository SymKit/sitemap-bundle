<?php

declare(strict_types=1);

use Symkit\SitemapBundle\Controller\SitemapController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('symkit_sitemap_index', '/sitemap.xml')
        ->controller(SitemapController::class)
        ->methods(['GET']);

    $routes->add('symkit_sitemap_show', '/sitemap/{name}.xml')
        ->controller(SitemapController::class)
        ->methods(['GET']);

    $routes->add('symkit_sitemap_show_paginated', '/sitemap/{name}-{page}.xml')
        ->controller(SitemapController::class)
        ->requirements(['page' => '\d+'])
        ->methods(['GET']);
};
