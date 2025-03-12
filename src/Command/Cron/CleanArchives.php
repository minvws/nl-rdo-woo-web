<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Domain\Publication\BatchDownload\BatchDownloadStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanArchives extends Command
{
    public function __construct(
        private readonly BatchDownloadRepository $batchRepository,
        private readonly BatchDownloadStorage $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:clean-archives')
            ->setDescription('Cleans up expired archives')
            ->setHelp('Cleans up expired archives')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $count = 0;

        $batches = $this->batchRepository->findExpiredBatchDownloads();
        foreach ($batches as $batch) {
            $this->storage->removeFileForBatch($batch);
            $this->batchRepository->remove($batch);
            $count++;
        }

        $output->writeln(sprintf('Removed %d expired archives', $count));

        return 0;
    }
}
