<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Model;

use DateTimeInterface;

final readonly class SitemapNews
{
    public function __construct(
        public string $publicationName,
        public string $publicationLanguage,
        public string $title,
        public DateTimeInterface $publicationDate,
    ) {
    }
}
