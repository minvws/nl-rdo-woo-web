<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Exception\DocumentWorkflowException;
use App\Message\UpdateDossierArchivesMessage;
use App\Repository\DocumentRepository;
use App\Service\DocumentWorkflow\DocumentWorkflowStatus;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class WithDrawDocumentHandler
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    public function __invoke(WithDrawDocumentCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $document = $command->document;

        $status = new DocumentWorkflowStatus($document);
        if (! $status->canWithdraw()) {
            throw DocumentWorkflowException::forActionNotAllowed($document, 'withdraw');
        }

        $this->entityStorageService->deleteAllFilesForEntity($document);
        $this->thumbStorage->deleteAllThumbsForEntity($document);

        $document->withdraw($command->reason, $command->explanation);
        $this->documentRepository->save($document, true);

        // Re-ingest the document, this will update all file metadata and overwrite any existing page content with an empty set.
        $this->ingestDispatcher->dispatchIngestMetadataOnlyCommandForEntity($document, true);

        foreach ($document->getDossiers() as $dossier) {
            $this->messageBus->dispatch(
                UpdateDossierArchivesMessage::forDossier($dossier)
            );
        }

        $this->messageBus->dispatch(
            new DocumentWithDrawnEvent($document, $command->reason, $command->explanation)
        );
    }
}
