<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessUploadedDocumentsHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private UploadEntityRepository $uploadEntityRepository,
        private ProcessUploadedDocumentAction $processUploadedDocumentAction,
        private DocumentFileService $documentFileService,
    ) {
    }

    public function __invoke(ProcessUploadedDocumentsCommand $message): void
    {
        $wooDecision = $this->wooDecisionRepository->find($message->wooDecisionId);
        if (! $wooDecision instanceof WooDecision) {
            return;
        }

        $uploadEntity = $this->uploadEntityRepository->find($message->uploadEntityId);
        if (! $uploadEntity instanceof UploadEntity) {
            return;
        }

        $this->processUploadedDocumentAction->execute($uploadEntity);

        $this->documentFileService->startSaveProcessingUploads($wooDecision);
    }
}
