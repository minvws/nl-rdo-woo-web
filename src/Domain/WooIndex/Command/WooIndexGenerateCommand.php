<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Command;

use Shared\Domain\WooIndex\WooIndexSitemapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'woo-index:generate',
    description: 'Generate new sitemaps for the WooIndex',
)]
class WooIndexGenerateCommand extends Command
{
    public function __construct(
        private readonly WooIndexSitemapService $wooIndexSitemapService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('cleanup', 'c', InputOption::VALUE_NONE, 'Cleanup older generate WooIndexes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('woo-index:generate');

        $wooIndexSitemap = $this->wooIndexSitemapService->generateSitemap();

        $io->success(sprintf('Successfully published new sitemap with id: %s', $wooIndexSitemap->getId()->toRfc4122()));

        if ($input->getOption('cleanup')) {
            $io->info('Cleaning up older sitemaps...');
            $this->wooIndexSitemapService->cleanupSitemaps();
        }

        $io->info('Done...');

        return Command::SUCCESS;
    }
}
