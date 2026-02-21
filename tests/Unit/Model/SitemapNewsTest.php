<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Model\SitemapNews;

final class SitemapNewsTest extends TestCase
{
    public function testConstruct(): void
    {
        $date = new DateTimeImmutable('2025-06-15');
        $news = new SitemapNews(
            publicationName: 'Example Times',
            publicationLanguage: 'en',
            title: 'Breaking News',
            publicationDate: $date,
        );

        self::assertSame('Example Times', $news->publicationName);
        self::assertSame('en', $news->publicationLanguage);
        self::assertSame('Breaking News', $news->title);
        self::assertSame($date, $news->publicationDate);
    }
}
