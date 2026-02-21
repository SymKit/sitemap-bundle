<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Priority;

use Symkit\SitemapBundle\Contract\SitemapPriorityCalculatorInterface;

final readonly class SitemapPriorityCalculator implements SitemapPriorityCalculatorInterface
{
    public function calculate(string $path): string
    {
        if ('/' === $path) {
            return '1.0';
        }

        $depth = \count(array_filter(explode('/', $path)));

        $priority = 1.0 - ($depth * 0.2);

        return (string) max(0.1, $priority);
    }
}
