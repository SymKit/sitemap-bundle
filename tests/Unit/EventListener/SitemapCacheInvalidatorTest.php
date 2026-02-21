<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\EventListener\SitemapCacheInvalidator;

final class SitemapCacheInvalidatorTest extends TestCase
{
    public function testOnInvalidateCallsCacheManagerInvalidate(): void
    {
        $cacheManager = $this->createMock(SitemapCacheManagerInterface::class);
        $cacheManager->expects(self::once())->method('invalidate');

        $invalidator = new SitemapCacheInvalidator($cacheManager);
        $invalidator->onInvalidate();
    }
}
