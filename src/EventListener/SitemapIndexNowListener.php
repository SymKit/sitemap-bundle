<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\EventListener;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symkit\SitemapBundle\Contract\SitemapNotifierInterface;

final readonly class SitemapIndexNowListener
{
    public function __construct(
        private SitemapNotifierInterface $notifier,
        private RouterInterface $router,
    ) {
    }

    public function onInvalidate(): void
    {
        $sitemapUrl = $this->router->generate(
            'symkit_sitemap_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->notifier->notify($sitemapUrl);
    }
}
