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
- **Video Support**: Built-in support for Google Video Sitemaps.
- **News Support**: Built-in support for Google News Sitemaps.
- **Multi-language (hreflang)**: `xhtml:link` alternates for multi-language sites.
- **Gzip Compression**: Optional gzip compression for sitemap responses.
- **Taggable Cache**: Optimized caching with tag-based invalidation.
- **Cache Warmup**: Automatic cache warming via `cache:warmup` or CLI command.
- **Console Commands**: `sitemap:generate` (warmup) and `sitemap:debug` (inspect loaders).
- **Profiler**: Symfony Web Debug Toolbar integration with loader stats and generation timing.
- **Async Generation**: Optional Messenger-based async sitemap regeneration (disabled by default).
- **IndexNow**: Automatic search engine notification on sitemap invalidation.
- **robots.txt**: Customizable `robots.txt` serving and automatic `Sitemap:` directive injection.
- **Logging**: PSR Logger integration on the `sitemap` monolog channel.
- **SOLID Architecture**: Contract-based interfaces, `final readonly` services, event-driven.

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
    gzip: false
    robots_txt:
        enabled: false
        content: ~            # Full robots.txt content (serves /robots.txt when set)
        inject_sitemap: true  # Auto-append Sitemap: directive
    cache:
        enabled: false
        pool: 'cache.app.taggable'
        tag: 'sitemap'
        ttl: 3600
    messenger:
        enabled: false
        transport: 'async'
    index_now:
        enabled: false
        api_key: ~ # Your IndexNow API key
        host: ~    # Optional: your site hostname
```

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `items_per_page` | `integer` | `25000` | Maximum URLs per sitemap chunk |
| `gzip` | `boolean` | `false` | Enable gzip compression for sitemap responses |
| `robots_txt.enabled` | `boolean` | `false` | Enable the robots.txt feature |
| `robots_txt.content` | `string` | `null` | Full robots.txt content — when set, the bundle serves `/robots.txt` with this content |
| `robots_txt.inject_sitemap` | `boolean` | `true` | Auto-append `Sitemap:` directive to robots.txt responses |
| `cache.enabled` | `boolean` | `false` | Enable caching for generated sitemaps |
| `cache.pool` | `string` | `cache.app.taggable` | Symfony cache pool (must support tagging) |
| `cache.tag` | `string` | `sitemap` | Cache tag for sitemap entries |
| `cache.ttl` | `integer` | `3600` | Cache TTL in seconds |
| `messenger.enabled` | `boolean` | `false` | Enable async sitemap generation via Messenger |
| `messenger.transport` | `string` | `async` | Messenger transport name |
| `index_now.enabled` | `boolean` | `false` | Enable IndexNow notifications on invalidation |
| `index_now.api_key` | `string` | `null` | IndexNow API key |
| `index_now.host` | `string` | `null` | Site hostname for IndexNow |

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

### 2. Multi-language (hreflang)

```php
use Symkit\SitemapBundle\Model\SitemapAlternate;
use Symkit\SitemapBundle\Model\SitemapUrl;

yield new SitemapUrl(
    loc: 'https://example.com/en/page',
    alternates: [
        new SitemapAlternate('fr', 'https://example.com/fr/page'),
        new SitemapAlternate('de', 'https://example.com/de/page'),
        new SitemapAlternate('x-default', 'https://example.com/en/page'),
    ],
);
```

### 3. Video Sitemaps

```php
use Symkit\SitemapBundle\Model\SitemapUrl;
use Symkit\SitemapBundle\Model\SitemapVideo;

yield new SitemapUrl(
    loc: 'https://example.com/videos/my-video',
    videos: [
        new SitemapVideo(
            thumbnailLoc: 'https://example.com/thumbs/my-video.jpg',
            title: 'My Video Title',
            description: 'A description of the video.',
            contentLoc: 'https://example.com/videos/my-video.mp4',
            duration: '600',
        ),
    ],
);
```

### 4. News Sitemaps

```php
use Symkit\SitemapBundle\Model\SitemapNews;
use Symkit\SitemapBundle\Model\SitemapUrl;

yield new SitemapUrl(
    loc: 'https://example.com/news/breaking-story',
    news: new SitemapNews(
        publicationName: 'Example Times',
        publicationLanguage: 'en',
        title: 'Breaking Story Title',
        publicationDate: new \DateTimeImmutable(),
    ),
);
```

### 5. Cache Invalidation

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

### 6. Custom Loader Name

By default, the sitemap name is the `snake_case` version of your loader class name. Override it with the tag `index` attribute:

```yaml
services:
    App\Sitemap\ProductSitemapLoader:
        tags:
            - { name: 'symkit_sitemap.loader', index: 'products' }
```

## Console Commands

### `sitemap:generate`

Generate and warm up all sitemap caches:

```bash
php bin/console sitemap:generate
php bin/console sitemap:generate --name=products
```

### `sitemap:debug`

Display registered loaders with URL counts and page numbers:

```bash
php bin/console sitemap:debug
```

## Events

| Event | When |
| :--- | :--- |
| `SitemapPreGenerateEvent` | Before sitemap XML generation |
| `SitemapPostGenerateEvent` | After sitemap XML generation (contains XML) |
| `SitemapInvalidateEvent` | When cache invalidation is triggered |

## Contributing

```bash
# Install dependencies
make install

# Run full quality pipeline
make quality
```

Available targets: `make cs-fix`, `make phpstan`, `make test`, `make deptrac`, `make infection`, `make quality`, `make ci`.

## License

MIT
