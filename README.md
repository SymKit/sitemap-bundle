# SitemapBundle

[![CI](https://github.com/symkit/sitemap-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/symkit/sitemap-bundle/actions)
[![Latest Version](https://img.shields.io/packagist/v/symkit/sitemap-bundle.svg)](https://packagist.org/packages/symkit/sitemap-bundle)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)

A modern, high-performance Symfony bundle for XML sitemap generation.

## Features

- **Performant**: Uses `iterable` and `yield` to handle millions of URLs with minimal memory footprint.
- **Automatic Chunking**: Splits large sitemaps (default > 25,000 URLs) into paginated files.
- **Pretty URLs**: Clean URL structures like `/sitemap/pages-1.xml`.
- **Image Support**: Built-in support for Google Image Sitemaps.
- **Taggable Cache**: Optimized caching with tag-based invalidation.
- **SOLID Architecture**: Contract-based interfaces, `final readonly` services, event-driven cache invalidation.

## Installation

```bash
composer require symkit/sitemap-bundle
```

## Routing

Import the bundle routes in your application:

```yaml
# config/routes/symkit_sitemap.yaml
symkit_sitemap:
    resource: '@SymkitSitemapBundle/config/routes.php'
```

This registers three routes:

| Route | Path | Description |
| :--- | :--- | :--- |
| `symkit_sitemap_index` | `/sitemap.xml` | Sitemap index |
| `symkit_sitemap_show` | `/sitemap/{name}.xml` | Single sitemap |
| `symkit_sitemap_show_paginated` | `/sitemap/{name}-{page}.xml` | Paginated sitemap |

## Configuration

```yaml
# config/packages/symkit_sitemap.yaml
symkit_sitemap:
    items_per_page: 25000
    cache:
        enabled: false
        pool: 'cache.app.taggable'
        tag: 'sitemap'
        ttl: 3600
```

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `items_per_page` | `integer` | `25000` | Maximum URLs per sitemap chunk |
| `cache.enabled` | `boolean` | `false` | Enable caching for generated sitemaps |
| `cache.pool` | `string` | `cache.app.taggable` | Symfony cache pool (must support tagging) |
| `cache.tag` | `string` | `sitemap` | Cache tag for sitemap entries |
| `cache.ttl` | `integer` | `3600` | Cache TTL in seconds |

## Usage

### 1. Create a Loader

Implement `SitemapLoaderInterface`. With autoconfiguration enabled, the bundle detects it automatically:

```php
namespace App\Sitemap;

use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Model\SitemapUrl;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ProductSitemapLoader implements SitemapLoaderInterface
{
    public function __construct(
        private ProductRepository $repository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countAllActive();
    }

    public function load(int $limit, int $offset): iterable
    {
        foreach ($this->repository->findBy([], null, $limit, $offset) as $product) {
            yield new SitemapUrl(
                loc: $this->urlGenerator->generate(
                    'app_product_show',
                    ['slug' => $product->getSlug()],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                lastmod: $product->getUpdatedAt(),
                changefreq: 'daily',
                priority: '0.8',
            );
        }
    }
}
```

### 2. Cache Invalidation

Dispatch `SitemapInvalidateEvent` or call `SitemapCacheManagerInterface::invalidate()`:

```php
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;

final readonly class ProductEventSubscriber
{
    public function __construct(
        private SitemapCacheManagerInterface $cacheManager,
    ) {
    }

    public function onProductUpdate(): void
    {
        $this->cacheManager->invalidate();
    }
}
```

### 3. Custom Loader Name

By default, the sitemap name is the `snake_case` version of your loader class name. Override it with the tag `index` attribute:

```yaml
services:
    App\Sitemap\ProductSitemapLoader:
        tags:
            - { name: 'symkit_sitemap.loader', index: 'products' }
```

## Contributing

```bash
# Install dependencies
make install

# Install git hooks (strips Co-authored-by from commits)
make install-hooks

# Run full quality pipeline
make quality
```

Available targets: `make cs-fix`, `make phpstan`, `make test`, `make deptrac`, `make infection`, `make quality`, `make ci`.

## License

MIT
