<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Exception;

use RuntimeException;

final class SitemapNotFoundException extends RuntimeException
{
    public static function forName(string $name): self
    {
        return new self(\sprintf('Sitemap loader "%s" not found.', $name));
    }
}
