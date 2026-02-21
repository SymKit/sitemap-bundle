<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;

#[AsCommand(
    name: 'sitemap:debug',
    description: 'Display registered sitemap loaders and their stats',
)]
final class SitemapDebugCommand extends Command
{
    public function __construct(
        private readonly SitemapRegistryInterface $registry,
        private readonly int $itemsPerPage,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sitemap Loaders');

        $loaders = $this->registry->getAllLoaders();

        if ([] === $loaders) {
            $io->warning('No sitemap loaders registered.');

            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($loaders as $name => $loader) {
            $count = $loader->count();
            $pages = max(1, (int) ceil($count / $this->itemsPerPage));
            $rows[] = [$name, $loader::class, $count, $pages];
        }

        $io->table(['Name', 'Class', 'URL count', 'Pages'], $rows);

        return Command::SUCCESS;
    }
}
