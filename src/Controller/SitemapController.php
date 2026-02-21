<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Controller;

use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class SitemapController
{
    public function __construct(
        private SitemapProviderInterface $provider,
    ) {
    }

    public function __invoke(?string $name = null, int $page = 1): Response
    {
        try {
            return new Response(
                content: $this->provider->provide($name, $page),
                headers: ['Content-Type' => 'application/xml'],
            );
        } catch (SitemapNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
    }
}
