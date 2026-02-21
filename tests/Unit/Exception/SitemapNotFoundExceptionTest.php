<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;

final class SitemapNotFoundExceptionTest extends TestCase
{
    public function testForNameCreatesExceptionWithMessage(): void
    {
        $exception = SitemapNotFoundException::forName('products');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Sitemap loader "products" not found.', $exception->getMessage());
    }
}
