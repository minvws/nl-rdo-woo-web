<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Shared\Domain\Publication\BatchDownload\BatchDownloadZipGenerator;
use Shared\Domain\Publication\BatchDownload\Command\GenerateBatchDownloadCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateBatchDownloadHandler
{
    public function __construct(
        private BatchDownloadRepository $repository,
        private LoggerInterface $logger,
        private BatchDownloadZipGenerator $zipGenerator,
    ) {
    }

    public function __invoke(GenerateBatchDownloadCommand $message): void
    {
        $batch = $this->repository->find($message->uuid);
        if (! $batch) {
            $this->logger->error('Cannot find batch download entity', [
                'id' => $message->uuid->toRfc4122(),
            ]);

            return;
        }

        $this->zipGenerator->generateArchive($batch);
    }
}
