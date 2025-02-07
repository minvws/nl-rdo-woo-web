<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileSetUpdatesCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProcessDocumentFileSetUpdatesHandler
{
    public function __construct(
        private DocumentFileSetRepository $repository,
        private LoggerInterface $logger,
        private DocumentFileDispatcher $dispatcher,
    ) {
    }

    public function __invoke(ProcessDocumentFileSetUpdatesCommand $message): void
    {
        $documentFileSet = $this->repository->find($message->documentFileSetId);
        if (! $documentFileSet) {
            $this->logger->warning('No DocumentFileSet found for this message', [
                'id' => $message->documentFileSetId,
            ]);

            return;
        }

        if (! $documentFileSet->getStatus()->isConfirmed()) {
            $this->logger->warning('Cannot process DocumentFileSet updates', [
                'id' => $documentFileSet->getId(),
                'status' => $documentFileSet->getStatus(),
            ]);

            return;
        }

        $this->repository->updateStatusTransactionally($documentFileSet, DocumentFileSetStatus::PROCESSING_UPDATES);

        foreach ($documentFileSet->getUpdates() as $update) {
            if (! $update->getStatus()->isPending()) {
                continue;
            }

            $this->dispatcher->dispatchProcessDocumentFileUpdateCommand($update);
        }
    }
}
