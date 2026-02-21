<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Model\SitemapVideo;

final class SitemapVideoTest extends TestCase
{
    public function testConstructWithAllParameters(): void
    {
        $video = new SitemapVideo(
            thumbnailLoc: 'https://example.com/thumb.jpg',
            title: 'My Video',
            description: 'A description',
            contentLoc: 'https://example.com/video.mp4',
            playerLoc: 'https://example.com/player',
            duration: '600',
        );

        self::assertSame('https://example.com/thumb.jpg', $video->thumbnailLoc);
        self::assertSame('My Video', $video->title);
        self::assertSame('A description', $video->description);
        self::assertSame('https://example.com/video.mp4', $video->contentLoc);
        self::assertSame('https://example.com/player', $video->playerLoc);
        self::assertSame('600', $video->duration);
    }

    public function testConstructWithDefaults(): void
    {
        $video = new SitemapVideo(
            thumbnailLoc: 'https://example.com/thumb.jpg',
            title: 'My Video',
            description: 'A description',
        );

        self::assertNull($video->contentLoc);
        self::assertNull($video->playerLoc);
        self::assertNull($video->duration);
    }
}
