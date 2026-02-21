<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\MessageHandler;

use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Message\GenerateSitemapMessage;

final readonly class GenerateSitemapHandler
{
    public function __construct(
        private SitemapProviderInterface $provider,
    ) {
    }

    public function __invoke(GenerateSitemapMessage $message): void
    {
        $this->provider->provide($message->name, $message->page);
    }
}
