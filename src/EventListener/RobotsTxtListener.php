<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class RobotsTxtListener
{
    public function __construct(
        private RouterInterface $router,
        private ?string $content = null,
        private bool $injectSitemap = true,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ('/robots.txt' !== $event->getRequest()->getPathInfo()) {
            return;
        }

        if (null === $this->content) {
            return;
        }

        $body = $this->content;

        if ($this->injectSitemap) {
            $body = $this->appendSitemapDirective($body);
        }

        $event->setResponse(new Response($body, 200, ['Content-Type' => 'text/plain']));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->injectSitemap) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        if ('/robots.txt' !== $event->getRequest()->getPathInfo()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type') ?? '';

        if (!str_contains($contentType, 'text/plain')) {
            return;
        }

        $content = $response->getContent();

        if (false === $content) {
            return;
        }

        $response->setContent($this->appendSitemapDirective($content));
    }

    private function appendSitemapDirective(string $content): string
    {
        $sitemapUrl = $this->router->generate(
            'symkit_sitemap_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $sitemapLine = \sprintf('Sitemap: %s', $sitemapUrl);

        if (str_contains($content, $sitemapLine)) {
            return $content;
        }

        return rtrim($content)."\n".$sitemapLine."\n";
    }
}
