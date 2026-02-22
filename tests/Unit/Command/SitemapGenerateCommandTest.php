<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\SitemapBundle\Command\SitemapGenerateCommand;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

final class SitemapGenerateCommandTest extends TestCase
{
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static function (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
                if ('command.generate.page_generated' === $id) {
                    return \sprintf(
                        '  Generated %s (page %d/%d, %d URLs)',
                        $parameters['%name%'] ?? '',
                        $parameters['%page%'] ?? 0,
                        $parameters['%chunks%'] ?? 0,
                        $parameters['%count%'] ?? 0,
                    );
                }

                return match ($id) {
                    'command.generate.title' => 'Generating sitemaps',
                    'command.generate.option_name' => 'Generate only a specific sitemap loader',
                    'command.generate.index_generated' => 'Generated sitemap index',
                    'command.generate.success' => 'All sitemaps generated successfully.',
                    default => $id,
                };
            },
        );

        return $translator;
    }

    public function testExecuteGeneratesAllSitemaps(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(100);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['pages' => $loader]);

        $calls = [];
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::exactly(2))
            ->method('provide')
            ->willReturnCallback(function (?string $name = null, int $page = 1) use (&$calls) {
                $calls[] = [$name, $page];

                return '<xml/>';
            });

        $command = new SitemapGenerateCommand($registry, $provider, 25000, $this->createTranslator());
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Generating sitemaps', $tester->getDisplay());
        self::assertStringContainsString('All sitemaps generated', $tester->getDisplay());
        self::assertSame([[null, 1], ['pages', 1]], $calls);
    }

    public function testExecuteWithMultiplePages(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(60000);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getAllLoaders')->willReturn(['products' => $loader]);

        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::exactly(4))
            ->method('provide')
            ->willReturn('<xml/>');

        $command = new SitemapGenerateCommand($registry, $provider, 25000, $this->createTranslator());
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('page 1/3', $tester->getDisplay());
        self::assertStringContainsString('page 3/3', $tester->getDisplay());
    }

    public function testExecuteWithNameOption(): void
    {
        $loader = $this->createMock(SitemapLoaderInterface::class);
        $loader->method('count')->willReturn(100);

        $registry = $this->createMock(SitemapRegistryInterface::class);
        $registry->method('getLoader')->with('pages')->willReturn($loader);

        $calls = [];
        $provider = $this->createMock(SitemapProviderInterface::class);
        $provider->expects(self::exactly(2))
            ->method('provide')
            ->willReturnCallback(function (?string $name = null, int $page = 1) use (&$calls) {
                $calls[] = [$name, $page];

                return '<xml/>';
            });

        $command = new SitemapGenerateCommand($registry, $provider, 25000, $this->createTranslator());
        $tester = new CommandTester($command);
        $tester->execute(['--name' => 'pages']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([[null, 1], ['pages', 1]], $calls);
    }
}
