<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Controller\SitemapController;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;

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
        $response = $controller(Request::create('/sitemap/pages.xml'), 'pages', 1);

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
        $response = $controller(Request::create('/sitemap.xml'));

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
        $controller(Request::create('/sitemap/unknown.xml'), 'unknown');
    }

    public function testInvokeCompressesWhenGzipEnabled(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->method('provide')->willReturn('<urlset/>');

        $controller = new SitemapController($provider, gzip: true);

        $request = Request::create('/sitemap.xml');
        $request->headers->set('Accept-Encoding', 'gzip, deflate');

        $response = $controller($request);

        self::assertSame('gzip', $response->headers->get('Content-Encoding'));
        self::assertSame(gzencode('<urlset/>'), $response->getContent());
    }

    public function testInvokeDoesNotCompressWhenClientDoesNotAcceptGzip(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->method('provide')->willReturn('<urlset/>');

        $controller = new SitemapController($provider, gzip: true);

        $request = Request::create('/sitemap.xml');
        $request->headers->set('Accept-Encoding', 'deflate');

        $response = $controller($request);

        self::assertNull($response->headers->get('Content-Encoding'));
        self::assertSame('<urlset/>', $response->getContent());
    }

    public function testInvokeDoesNotCompressWhenGzipDisabled(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->method('provide')->willReturn('<urlset/>');

        $controller = new SitemapController($provider, gzip: false);

        $request = Request::create('/sitemap.xml');
        $request->headers->set('Accept-Encoding', 'gzip');

        $response = $controller($request);

        self::assertNull($response->headers->get('Content-Encoding'));
    }
}
