<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Event\SitemapPostGenerateEvent;
use Symkit\SitemapBundle\Event\SitemapPreGenerateEvent;

final readonly class SitemapGenerator implements SitemapGeneratorInterface
{
    public function __construct(
        private SitemapUrlGenerator $urlGenerator,
        private SitemapIndexGenerator $indexGenerator,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function generate(?string $name = null, int $page = 1): string
    {
        $this->logger?->debug('Generating sitemap.', ['name' => $name, 'page' => $page]);
        $this->eventDispatcher->dispatch(new SitemapPreGenerateEvent($name, $page));

        if (null === $name) {
            $xml = $this->indexGenerator->generate();
        } else {
            $xml = $this->urlGenerator->generate($name, $page);
        }

        $this->eventDispatcher->dispatch(new SitemapPostGenerateEvent($name, $page, $xml));
        $this->logger?->info('Sitemap generated.', [
            'name' => $name ?? 'index',
            'page' => $page,
            'size' => \strlen($xml),
        ]);

        return $xml;
    }
}
