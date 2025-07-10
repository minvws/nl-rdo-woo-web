<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Service\Stats\WorkerStatsRepository;
use Carbon\CarbonImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanWorkerStats extends Command
{
    public function __construct(
        private readonly WorkerStatsRepository $workerStatsRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:clean-worker-status')
            ->setDescription('Cleans up all worker stats more than one week old')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $this->workerStatsRepository->removeOldEntries(
            CarbonImmutable::now()->subWeek(),
        );

        $output->writeln('Done!');

        return Command::SUCCESS;
    }
}
