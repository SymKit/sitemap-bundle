<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            $this->translator->trans('command.generate.option_name', [], 'SymkitSitemapBundle'),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->translator->trans('command.generate.title', [], 'SymkitSitemapBundle'));

        /** @var string|null $loaderName */
        $loaderName = $input->getOption('name');

        $this->provider->provide();
        $io->text($this->translator->trans('command.generate.index_generated', [], 'SymkitSitemapBundle'));

        $loaders = null !== $loaderName
            ? [$loaderName => $this->registry->getLoader($loaderName)]
            : $this->registry->getAllLoaders();

        foreach ($loaders as $name => $loader) {
            $totalItems = $loader->count();
            $chunks = max(1, (int) ceil($totalItems / $this->itemsPerPage));

            for ($page = 1; $page <= $chunks; ++$page) {
                $this->provider->provide($name, $page);
                $io->text($this->translator->trans('command.generate.page_generated', [
                    '%name%' => $name,
                    '%page%' => $page,
                    '%chunks%' => $chunks,
                    '%count%' => $totalItems,
                ], 'SymkitSitemapBundle'));
            }
        }

        $io->success($this->translator->trans('command.generate.success', [], 'SymkitSitemapBundle'));

        return Command::SUCCESS;
    }
}
