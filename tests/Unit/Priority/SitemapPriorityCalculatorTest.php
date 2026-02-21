<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Priority;

use PHPUnit\Framework\TestCase;
use Symkit\SitemapBundle\Priority\SitemapPriorityCalculator;

final class SitemapPriorityCalculatorTest extends TestCase
{
    private SitemapPriorityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SitemapPriorityCalculator();
    }

    public function testRootPathReturnsMaxPriority(): void
    {
        self::assertSame('1.0', $this->calculator->calculate('/'));
    }

    public function testFirstLevelPathReturns08(): void
    {
        self::assertSame('0.8', $this->calculator->calculate('/about'));
    }

    public function testSecondLevelPathReturns06(): void
    {
        self::assertSame('0.6', $this->calculator->calculate('/blog/post'));
    }

    public function testDeepPathReturnsMinimumPriority(): void
    {
        self::assertSame('0.1', $this->calculator->calculate('/a/b/c/d/e/f'));
    }
}
