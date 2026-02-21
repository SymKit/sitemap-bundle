<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Generator;

use DateTimeInterface;
use Symkit\SitemapBundle\Model\SitemapUrl;
use XMLWriter;

final class SitemapXmlBuilder
{
    private XMLWriter $writer;

    public function __construct()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
    }

    /**
     * @param iterable<SitemapUrl> $urls
     */
    public function buildUrlSet(iterable $urls): string
    {
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $this->writer->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
        $this->writer->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');

        foreach ($urls as $url) {
            $this->writeUrl($url);
        }

        $this->writer->endElement();
        $this->writer->endDocument();

        return $this->writer->outputMemory();
    }

    /**
     * @param list<array{loc: string, lastmod?: string|null}> $sitemaps
     */
    public function buildIndex(array $sitemaps): string
    {
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement('sitemapindex');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($sitemaps as $sitemap) {
            $this->writer->startElement('sitemap');
            $this->writer->writeElement('loc', $sitemap['loc']);

            if (isset($sitemap['lastmod'])) {
                $this->writer->writeElement('lastmod', $sitemap['lastmod']);
            }

            $this->writer->endElement();
        }

        $this->writer->endElement();
        $this->writer->endDocument();

        return $this->writer->outputMemory();
    }

    private function writeUrl(SitemapUrl $url): void
    {
        $this->writer->startElement('url');
        $this->writer->writeElement('loc', $url->loc);

        if (null !== $url->lastmod) {
            $this->writer->writeElement('lastmod', $url->lastmod->format(DateTimeInterface::ATOM));
        }

        if (null !== $url->changefreq) {
            $this->writer->writeElement('changefreq', $url->changefreq);
        }

        if (null !== $url->priority) {
            $this->writer->writeElement('priority', $url->priority);
        }

        foreach ($url->alternates as $alternate) {
            $this->writer->startElement('xhtml:link');
            $this->writer->writeAttribute('rel', 'alternate');
            $this->writer->writeAttribute('hreflang', $alternate->hreflang);
            $this->writer->writeAttribute('href', $alternate->href);
            $this->writer->endElement();
        }

        foreach ($url->images as $image) {
            $this->writer->startElement('image:image');
            $this->writer->writeElement('image:loc', $image['url'] ?? $image['loc'] ?? '');

            if (isset($image['title'])) {
                $this->writer->writeElement('image:title', $image['title']);
            }

            if (isset($image['caption'])) {
                $this->writer->writeElement('image:caption', $image['caption']);
            }

            $this->writer->endElement();
        }

        foreach ($url->videos as $video) {
            $this->writer->startElement('video:video');
            $this->writer->writeElement('video:thumbnail_loc', $video->thumbnailLoc);
            $this->writer->writeElement('video:title', $video->title);
            $this->writer->writeElement('video:description', $video->description);

            if (null !== $video->contentLoc) {
                $this->writer->writeElement('video:content_loc', $video->contentLoc);
            }

            if (null !== $video->playerLoc) {
                $this->writer->writeElement('video:player_loc', $video->playerLoc);
            }

            if (null !== $video->duration) {
                $this->writer->writeElement('video:duration', $video->duration);
            }

            $this->writer->endElement();
        }

        if (null !== $url->news) {
            $this->writer->startElement('news:news');
            $this->writer->startElement('news:publication');
            $this->writer->writeElement('news:name', $url->news->publicationName);
            $this->writer->writeElement('news:language', $url->news->publicationLanguage);
            $this->writer->endElement();
            $this->writer->writeElement('news:publication_date', $url->news->publicationDate->format(DateTimeInterface::ATOM));
            $this->writer->writeElement('news:title', $url->news->title);
            $this->writer->endElement();
        }

        $this->writer->endElement();
    }
}
