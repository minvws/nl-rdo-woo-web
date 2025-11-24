<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InventoryRefresh extends Command
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly InquiryRepository $inquiryRepository,
        private readonly ProductionReportDispatcher $productionReportDispatcher,
        private readonly WooDecisionDispatcher $wooDecisionDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('woopie:inventory:refresh')
            ->setDescription('Triggers an inventory refresh for all woo-decisions and inquiries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $output->writeln('Triggering inventory refresh for all woo-decisions...');
        foreach ($this->wooDecisionRepository->getPubliclyAvailable() as $wooDecision) {
            $this->productionReportDispatcher->dispatchGenerateInventoryCommand($wooDecision->getId());
        }

        $output->writeln('Triggering inventory refresh for all inquiries...');
        foreach ($this->inquiryRepository->findAll() as $inquiry) {
            $this->wooDecisionDispatcher->dispatchGenerateInquiryInventoryCommand($inquiry->getId());
        }

        $output->writeln("All refreshes have been scheduled, processing by workers might take some time\n");

        return Command::SUCCESS;
    }
}
