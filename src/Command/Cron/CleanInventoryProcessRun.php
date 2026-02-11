<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use Shared\Exception\ProcessInventoryException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(name: 'woopie:cron:clean-inventory-process-run', description: 'Marks expired inventory process runs as failed')]
class CleanInventoryProcessRun extends Command
{
    public function __construct(
        private readonly ProductionReportProcessRunRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Marks expired inventory process runs as failed');
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

        return self::SUCCESS;
    }
}
