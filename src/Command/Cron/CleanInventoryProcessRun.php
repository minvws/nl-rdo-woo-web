<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Exception\ProcessInventoryException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanInventoryProcessRun extends Command
{
    public function __construct(
        private readonly ProductionReportProcessRunRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:clean-inventory-process-run')
            ->setDescription('Marks expired inventory process runs as failed')
            ->setHelp('Marks expired inventory process runs as failed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $count = 0;

        $expiredRuns = $this->repository->findExpiredRuns();
        foreach ($expiredRuns as $expiredRun) {
            $expiredRun->addGenericException(ProcessInventoryException::forMaxRuntimeExceeded());
            $expiredRun->fail();

            $this->repository->save($expiredRun, true);
            $count++;
        }

        $output->writeln(sprintf('Marked %d runs as expired', $count));

        return 0;
    }
}
