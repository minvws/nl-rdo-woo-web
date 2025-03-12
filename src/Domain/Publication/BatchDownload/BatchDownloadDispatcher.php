<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\Command\GenerateBatchDownloadCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class BatchDownloadDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchGenerateBatchDownloadCommand(BatchDownload $batch): void
    {
        $this->messageBus->dispatch(
            new GenerateBatchDownloadCommand($batch->getId()),
        );
    }
}
