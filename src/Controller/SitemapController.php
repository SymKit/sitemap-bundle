<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Exception\SitemapNotFoundException;

final readonly class SitemapController
{
    public function __construct(
        private SitemapProviderInterface $provider,
        private bool $gzip = false,
    ) {
    }

    public function __invoke(Request $request, ?string $name = null, int $page = 1): Response
    {
        try {
            $xml = $this->provider->provide($name, $page);
        } catch (SitemapNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        $headers = ['Content-Type' => 'application/xml'];

        if ($this->gzip && str_contains((string) $request->headers->get('Accept-Encoding'), 'gzip')) {
            $compressed = gzencode($xml);

            if (false !== $compressed) {
                $xml = $compressed;
                $headers['Content-Encoding'] = 'gzip';
            }
        }

        return new Response(content: $xml, headers: $headers);
    }
}
