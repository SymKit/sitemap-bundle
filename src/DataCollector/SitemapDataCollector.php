<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Event\SitemapPostGenerateEvent;
use Symkit\SitemapBundle\Event\SitemapPreGenerateEvent;
use Throwable;

final class SitemapDataCollector extends AbstractDataCollector
{
    /** @var array<int, array{name: ?string, page: int, startTime: float}> */
    private array $pending = [];

    public function __construct(
        private readonly SitemapRegistryInterface $registry,
        private readonly bool $cacheEnabled,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $loaders = [];
        foreach ($this->registry->getAllLoaders() as $name => $loader) {
            $loaders[$name] = [
                'class' => $loader::class,
                'count' => $loader->count(),
            ];
        }

        $this->data['loaders'] = $loaders;
        $this->data['cache_enabled'] = $this->cacheEnabled;
        $this->data['generations'] ??= [];
    }

    public function onPreGenerate(SitemapPreGenerateEvent $event): void
    {
        $this->pending[] = [
            'name' => $event->name,
            'page' => $event->page,
            'startTime' => microtime(true),
        ];
    }

    public function onPostGenerate(SitemapPostGenerateEvent $event): void
    {
        $pending = array_pop($this->pending);
        if (null === $pending) {
            return;
        }

        $this->data['generations'][] = [
            'name' => $event->name,
            'page' => $event->page,
            'duration' => microtime(true) - $pending['startTime'],
            'size' => \strlen($event->xml),
        ];
    }

    public static function getTemplate(): string
    {
        return '@SymkitSitemap/data_collector/sitemap.html.twig';
    }

    public function getName(): string
    {
        return 'symkit_sitemap';
    }

    /**
     * @return array<string, array{class: string, count: int}>
     */
    public function getLoaders(): array
    {
        return $this->data['loaders'] ?? [];
    }

    public function isCacheEnabled(): bool
    {
        return $this->data['cache_enabled'] ?? false;
    }

    /**
     * @return list<array{name: ?string, page: int, duration: float, size: int}>
     */
    public function getGenerations(): array
    {
        return $this->data['generations'] ?? [];
    }

    public function getTotalUrls(): int
    {
        $total = 0;
        foreach ($this->getLoaders() as $loader) {
            $total += $loader['count'];
        }

        return $total;
    }
}
