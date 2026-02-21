<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Model\SitemapAlternate;

final class SitemapAlternateTest extends TestCase
{
    public function testConstruct(): void
    {
        $alternate = new SitemapAlternate(hreflang: 'fr', href: 'https://example.com/fr/page');

        self::assertSame('fr', $alternate->hreflang);
        self::assertSame('https://example.com/fr/page', $alternate->href);
    }
}
