<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symkit\SitemapBundle\EventListener\RobotsTxtListener;

final class RobotsTxtListenerTest extends TestCase
{
    public function testOnKernelResponseAppendsToRobotsTxt(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap.xml');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/robots.txt');
        $response = new Response("User-agent: *\nDisallow:", 200, ['Content-Type' => 'text/plain']);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringEndsWith("Sitemap: https://example.com/sitemap.xml\n", $content);
        self::assertStringContainsString("User-agent: *\nDisallow:\nSitemap:", $content);
    }

    public function testOnKernelResponseIgnoresNonRobotsTxtPath(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/other-path');
        $response = new Response('OK', 200, ['Content-Type' => 'text/plain']);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertStringNotContainsString('Sitemap:', (string) $response->getContent());
    }

    public function testOnKernelResponseIgnoresNonTextPlainContentType(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/robots.txt');
        $response = new Response('<html/>', 200, ['Content-Type' => 'text/html']);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertStringNotContainsString('Sitemap:', (string) $response->getContent());
    }

    public function testOnKernelResponseDoesNotDuplicateSitemapLine(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap.xml');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/robots.txt');
        $response = new Response(
            "User-agent: *\nSitemap: https://example.com/sitemap.xml",
            200,
            ['Content-Type' => 'text/plain'],
        );

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertSame(1, substr_count((string) $response->getContent(), 'Sitemap:'));
    }

    public function testOnKernelResponseIgnoresSubRequests(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/robots.txt');
        $response = new Response('body', 200, ['Content-Type' => 'text/plain']);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertStringNotContainsString('Sitemap:', (string) $response->getContent());
    }

    public function testOnKernelResponseHandlesFalseContent(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap.xml');

        $listener = new RobotsTxtListener($router);

        $request = Request::create('/robots.txt');
        $response = $this->createMock(Response::class);
        $response->headers = new ResponseHeaderBag();
        $response->headers->set('Content-Type', 'text/plain');
        $response->method('getContent')->willReturn(false);
        $response->expects(self::never())->method('setContent');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);
    }

    public function testOnKernelResponseSkipsWhenInjectSitemapDisabled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new RobotsTxtListener($router, injectSitemap: false);

        $request = Request::create('/robots.txt');
        $response = new Response("User-agent: *\nDisallow:", 200, ['Content-Type' => 'text/plain']);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertStringNotContainsString('Sitemap:', (string) $response->getContent());
    }

    public function testOnKernelRequestServesCustomContent(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap.xml');

        $customContent = "User-agent: *\nDisallow: /admin/";
        $listener = new RobotsTxtListener($router, content: $customContent);

        $request = Request::create('/robots.txt');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString("User-agent: *\nDisallow: /admin/", $content);
        self::assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
    }

    public function testOnKernelRequestServesCustomContentWithoutSitemapWhenDisabled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $customContent = "User-agent: *\nDisallow: /admin/";
        $listener = new RobotsTxtListener($router, content: $customContent, injectSitemap: false);

        $request = Request::create('/robots.txt');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertNotNull($response);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertSame("User-agent: *\nDisallow: /admin/", $content);
        self::assertStringNotContainsString('Sitemap:', $content);
    }

    public function testOnKernelRequestDoesNothingWhenContentIsNull(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $listener = new RobotsTxtListener($router, content: null);

        $request = Request::create('/robots.txt');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresNonRobotsTxtPath(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $listener = new RobotsTxtListener($router, content: 'User-agent: *');

        $request = Request::create('/other-path');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresSubRequests(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $listener = new RobotsTxtListener($router, content: 'User-agent: *');

        $request = Request::create('/robots.txt');
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }
}
