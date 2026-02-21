<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symkit\SitemapBundle\Contract\SitemapNotifierInterface;
use Symkit\SitemapBundle\EventListener\SitemapIndexNowListener;

final class SitemapIndexNowListenerTest extends TestCase
{
    public function testOnInvalidateNotifiesWithSitemapUrl(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://example.com/sitemap.xml');

        $notifier = $this->createMock(SitemapNotifierInterface::class);
        $notifier->expects(self::once())
            ->method('notify')
            ->with('https://example.com/sitemap.xml');

        $listener = new SitemapIndexNowListener($notifier, $router);
        $listener->onInvalidate();
    }
}
