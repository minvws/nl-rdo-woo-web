<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Command;

use App\Domain\WooIndex\WooIndex;
use App\Domain\WooIndex\WooIndexFileManager;
use App\Domain\WooIndex\WooIndexRunOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'woo-index:generate',
    description: 'Generate new sitemaps for the WooIndex',
)]
class WooIndexGenerateCommand extends Command
{
    public function __construct(
        private WooIndex $wooIndex,
        private WooIndexFileManager $fileManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('woo-index:generate');

        $path = $this->wooIndex->create(new WooIndexRunOptions());

        $publishResult = $this->fileManager->publish($path);

        if ($publishResult === false) {
            $io->error('Failed publishing the sitemap');

            return Command::FAILURE;
        }

        $this->fileManager->cleanupPublished(5);

        $io->success(sprintf('Successfully published new sitemap to: %s', $publishResult));

        return Command::SUCCESS;
    }
}
