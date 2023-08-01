<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Entity\BatchDownload;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanArchives extends Command
{
    protected EntityManagerInterface $doctrine;
    protected FilesystemOperator $storage;
    protected LoggerInterface $logger;

    public function __construct(EntityManagerInterface $doctrine, FilesystemOperator $storage, LoggerInterface $logger)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->storage = $storage;
        $this->logger = $logger;
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

        $batches = $this->doctrine->getRepository(BatchDownload::class)->findExpiredArchives();
        foreach ($batches as $batch) {
            $archivePath = sprintf('batch-%s.zip', $batch->getId()->toBase58());
            try {
                $this->storage->delete($archivePath);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Failed to remove archive %s: %s', $archivePath, $e->getMessage()));
                continue;
            }

            $this->doctrine->remove($batch);
            $count++;
        }

        $this->doctrine->flush();

        $output->writeln(sprintf('Removed %d expired archives', $count));

        return 0;
    }
}
