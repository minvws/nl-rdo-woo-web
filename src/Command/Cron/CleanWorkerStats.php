<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Carbon\CarbonImmutable;
use Shared\Service\Stats\WorkerStatsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'woopie:cron:clean-worker-status', description: 'Cleans up all worker stats more than one week old')]
class CleanWorkerStats extends Command
{
    public function __construct(
        private readonly WorkerStatsRepository $workerStatsRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $this->workerStatsRepository->removeOldEntries(
            CarbonImmutable::now()->subWeek(),
        );

        $output->writeln('Done!');

        return self::SUCCESS;
    }
}
