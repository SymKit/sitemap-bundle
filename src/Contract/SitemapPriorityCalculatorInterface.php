<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Contract;

interface SitemapPriorityCalculatorInterface
{
    public function calculate(string $path): string;
}
