<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symkit\SitemapBundle\Command\SitemapDebugCommand;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

final class SitemapDebugCommandTest extends TestCase
{
    public function testExecuteDisplaysLoaders(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(500);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['pages' => $loader]);

        $command = new SitemapDebugCommand($registry, 25000);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Sitemap Loaders', $output);
        self::assertStringContainsString('pages', $output);
        self::assertStringContainsString('500', $output);
        self::assertStringContainsString('1', $output);
    }

    public function testExecuteDisplaysCorrectPageCount(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(60000);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['products' => $loader]);

        $command = new SitemapDebugCommand($registry, 25000);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('products', $output);
        self::assertStringContainsString('60000', $output);
        self::assertStringContainsString('3', $output);
    }

    public function testExecuteWithNoLoaders(): void
    {
        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn([]);

        $command = new SitemapDebugCommand($registry, 25000);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No sitemap loaders registered', $tester->getDisplay());
    }
}
