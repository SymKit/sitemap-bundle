<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Controller\SitemapController;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SitemapControllerTest extends TestCase
{
    public function testInvokeReturnsXmlResponse(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::once())
            ->method('provide')
            ->with('pages', 1)
            ->willReturn('<urlset/>');

        $controller = new SitemapController($provider);
        $response = $controller('pages', 1);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml', $response->headers->get('Content-Type'));
        self::assertSame('<urlset/>', $response->getContent());
    }

    public function testInvokeReturnsIndexWhenNoName(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::once())
            ->method('provide')
            ->with(null, 1)
            ->willReturn('<sitemapindex/>');

        $controller = new SitemapController($provider);
        $response = $controller();

        self::assertSame('<sitemapindex/>', $response->getContent());
    }

    public function testInvokeThrows404OnSitemapNotFound(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::once())
            ->method('provide')
            ->willThrowException(SitemapNotFoundException::forName('unknown'));

        $controller = new SitemapController($provider);

        $this->expectException(NotFoundHttpException::class);
        $controller('unknown');
    }
}
