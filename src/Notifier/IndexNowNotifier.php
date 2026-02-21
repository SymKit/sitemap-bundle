<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Notifier;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symkit\SitemapBundle\Contract\SitemapNotifierInterface;
use Throwable;

final readonly class IndexNowNotifier implements SitemapNotifierInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private ?string $host = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function notify(string $sitemapUrl): void
    {
        $params = [
            'url' => $sitemapUrl,
            'key' => $this->apiKey,
        ];

        if (null !== $this->host) {
            $params['host'] = $this->host;
        }

        try {
            $this->httpClient->request('GET', 'https://api.indexnow.org/indexnow', [
                'query' => $params,
            ]);

            $this->logger?->info('IndexNow notified for sitemap URL.', ['url' => $sitemapUrl]);
        } catch (Throwable $e) {
            $this->logger?->warning('IndexNow notification failed.', [
                'url' => $sitemapUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
