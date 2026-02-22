<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->translator->trans('command.debug.title', [], 'SymkitSitemapBundle'));

        $loaders = $this->registry->getAllLoaders();

        if ([] === $loaders) {
            $io->warning($this->translator->trans('command.debug.no_loaders', [], 'SymkitSitemapBundle'));

            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($loaders as $name => $loader) {
            $count = $loader->count();
            $pages = max(1, (int) ceil($count / $this->itemsPerPage));
            $rows[] = [$name, $loader::class, $count, $pages];
        }

        $io->table(
            [
                $this->translator->trans('command.debug.table_name', [], 'SymkitSitemapBundle'),
                $this->translator->trans('command.debug.table_class', [], 'SymkitSitemapBundle'),
                $this->translator->trans('command.debug.table_url_count', [], 'SymkitSitemapBundle'),
                $this->translator->trans('command.debug.table_pages', [], 'SymkitSitemapBundle'),
            ],
            $rows,
        );

        return Command::SUCCESS;
    }
}
