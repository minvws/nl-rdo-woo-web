<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Shared\Domain\Publication\BatchDownload\BatchDownloadStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(name: 'woopie:cron:clean-archives', description: 'Cleans up expired archives')]
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
        $this
            ->setHelp('Cleans up expired archives');
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

        return self::SUCCESS;
    }
}
