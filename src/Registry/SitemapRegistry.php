<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Registry;

use ReflectionClass;

use function Symfony\Component\String\u;

use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;

final readonly class SitemapRegistry implements SitemapRegistryInterface
{
    /** @var array<string, SitemapLoaderInterface> */
    private array $loaders;

    /**
     * @param iterable<string|int, SitemapLoaderInterface> $loaders
     */
    public function __construct(
        iterable $loaders,
    ) {
        $processed = [];
        foreach ($loaders as $index => $loader) {
            $name = $this->resolveName($index, $loader);
            $processed[$name] = $loader;
        }
        $this->loaders = $processed;
    }

    public function getLoader(string $name): SitemapLoaderInterface
    {
        return $this->loaders[$name] ?? throw SitemapNotFoundException::forName($name);
    }

    public function getAllLoaders(): array
    {
        return $this->loaders;
    }

    private function resolveName(string|int $index, SitemapLoaderInterface $loader): string
    {
        if (\is_string($index) && !is_numeric($index)) {
            return $index;
        }

        $reflection = new ReflectionClass($loader);

        return u($reflection->getShortName())->snake()->toString();
    }
}
