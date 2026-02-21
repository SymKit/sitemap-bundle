<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\MessageHandler;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Message\GenerateSitemapMessage;
use Symkit\SitemapBundle\MessageHandler\GenerateSitemapHandler;

final class GenerateSitemapHandlerTest extends TestCase
{
    public function testInvokeCallsProvider(): void
    {
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::once())
            ->method('provide')
            ->with('pages', 2);

        $handler = new GenerateSitemapHandler($provider);
        $handler(new GenerateSitemapMessage('pages', 2));
    }
}
