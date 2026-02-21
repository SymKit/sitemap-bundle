<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Notifier;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symkit\SitemapBundle\Notifier\IndexNowNotifier;

final class IndexNowNotifierTest extends TestCase
{
    public function testNotifySendsRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.indexnow.org/indexnow', [
                'query' => [
                    'url' => 'https://example.com/sitemap.xml',
                    'key' => 'my-api-key',
                ],
            ])
            ->willReturn($response);

        $notifier = new IndexNowNotifier($httpClient, 'my-api-key');
        $notifier->notify('https://example.com/sitemap.xml');
    }

    public function testNotifyIncludesHostWhenProvided(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.indexnow.org/indexnow', [
                'query' => [
                    'url' => 'https://example.com/sitemap.xml',
                    'key' => 'my-api-key',
                    'host' => 'example.com',
                ],
            ])
            ->willReturn($response);

        $notifier = new IndexNowNotifier($httpClient, 'my-api-key', 'example.com');
        $notifier->notify('https://example.com/sitemap.xml');
    }

    public function testNotifyHandlesExceptionGracefully(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')
            ->willThrowException(new RuntimeException('Network error'));

        $notifier = new IndexNowNotifier($httpClient, 'my-api-key');
        $notifier->notify('https://example.com/sitemap.xml');

        $this->expectNotToPerformAssertions();
    }
}
