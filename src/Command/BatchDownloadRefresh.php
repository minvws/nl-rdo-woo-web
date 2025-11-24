<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BatchDownloadRefresh extends Command
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly BatchDownloadService $batchDownloadService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('woopie:batchdownload:refresh')
            ->setDescription('Triggers a BatchDownload refresh for all public woo-decisions and inquiries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $output->writeln('Refreshing archives for all dossiers...');
        foreach ($this->wooDecisionRepository->getPubliclyAvailable() as $wooDecision) {
            $this->batchDownloadService->refresh(BatchDownloadScope::forWooDecision($wooDecision));
        }

        $output->writeln("Done\n");

        return 0;
    }
}
