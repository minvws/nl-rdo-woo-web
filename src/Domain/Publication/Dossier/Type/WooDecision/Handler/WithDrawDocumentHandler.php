<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Ingest\IngestMetadataOnlyMessage;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\Document;
use App\Exception\DocumentWorkflowException;
use App\Message\UpdateDossierArchivesMessage;
use App\Repository\DocumentRepository;
use App\Service\DocumentWorkflow\DocumentWorkflowStatus;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AsMessageHandler]
readonly class WithDrawDocumentHandler
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentStorageService $documentStorage,
        private ThumbnailStorageService $thumbStorage,
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
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

        $this->documentStorage->deleteAllFilesForDocument($document);
        $this->thumbStorage->deleteAllThumbsForDocument($document);

        $document->withdraw($command->reason, $command->explanation);
        $this->documentRepository->save($document, true);

        // Re-ingest the document, this will update all file metadata and overwrite any existing page content with an empty set.
        $this->messageBus->dispatch(
            new IngestMetadataOnlyMessage($document->getId(), Document::class, true)
        );

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
