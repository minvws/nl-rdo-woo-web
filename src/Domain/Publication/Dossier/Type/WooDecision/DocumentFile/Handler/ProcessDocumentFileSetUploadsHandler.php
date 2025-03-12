<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUploadsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProcessDocumentFileSetUploadsHandler
{
    public function __construct(
        private DocumentFileSetRepository $repository,
        private LoggerInterface $logger,
        private DocumentFileDispatcher $dispatcher,
    ) {
    }

    public function __invoke(ProcessDocumentFileSetUploadsCommand $message): void
    {
        $documentFileSet = $this->repository->find($message->documentFileSetId);
        if (! $documentFileSet) {
            $this->logger->warning('No DocumentFileSet found for this message', [
                'id' => $message->documentFileSetId->toRfc4122(),
            ]);

            return;
        }

        if (! $documentFileSet->getStatus()->isProcessingUploads()) {
            $this->logger->warning('Cannot process DocumentFileSet uploads', [
                'id' => $documentFileSet->getId()->toRfc4122(),
                'status' => $documentFileSet->getStatus()->value,
            ]);

            return;
        }

        foreach ($documentFileSet->getUploads() as $upload) {
            if (! $upload->getStatus()->isUploaded()) {
                continue;
            }

            $this->dispatcher->dispatchProcessDocumentFileUploadCommand($upload);
        }
    }
}
