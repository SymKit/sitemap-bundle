<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

#[AsCommand(
    name: 'sitemap:generate',
    description: 'Generate and warm up sitemap cache',
)]
final class SitemapGenerateCommand extends Command
{
    public function __construct(
        private readonly SitemapRegistryInterface $registry,
        private readonly SitemapProviderInterface $provider,
        private readonly int $itemsPerPage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Generate only a specific sitemap loader');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating sitemaps');

        /** @var string|null $loaderName */
        $loaderName = $input->getOption('name');

        $this->provider->provide();
        $io->text('Generated sitemap index');

        $loaders = null !== $loaderName
            ? [$loaderName => $this->registry->getLoader($loaderName)]
            : $this->registry->getAllLoaders();

        foreach ($loaders as $name => $loader) {
            $totalItems = $loader->count();
            $chunks = max(1, (int) ceil($totalItems / $this->itemsPerPage));

            for ($page = 1; $page <= $chunks; ++$page) {
                $this->provider->provide($name, $page);
                $io->text(\sprintf('  Generated %s (page %d/%d, %d URLs)', $name, $page, $chunks, $totalItems));
            }
        }

        $io->success('All sitemaps generated successfully.');

        return Command::SUCCESS;
    }
}
