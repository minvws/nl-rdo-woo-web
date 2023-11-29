<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\GenerateSanitizedInventoryMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Message\UpdateDossierMessage;
use App\Repository\DocumentRepository;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DocumentUpdater $documentUpdater,
        private readonly DocumentRepository $documentRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly InquiryService $inquiryService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function applyChangesetToDatabase(
        Dossier $dossier,
        InventoryReaderInterface $reader,
        InventoryChangeset $changeset,
        RunProgress $runProgress,
    ): void {
        $documentGenerator = $reader->getDocumentMetadataGenerator($dossier);

        $currentProgress = $runProgress->getCurrentCount();
        foreach ($documentGenerator as $inventoryItem) {
            $rowIndex = $inventoryItem->getIndex();
            $runProgress->update($currentProgress + $rowIndex);

            $documentMetadata = $inventoryItem->getDocumentMetadata();
            if (! $documentMetadata instanceof DocumentMetadata) {
                continue;
            }

            $documentNr = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);
            $document = $this->loadDocumentFromDossierEntity($dossier, $documentNr->getValue());

            $action = $changeset->getAction($documentNr);

            if ($action === null) {
                continue;
            }

            if ($action === InventoryChangeset::ACTION_CREATE && $document === null) {
                $document = new Document();

                $document->setDocumentNr($documentNr->getValue());
                $this->doctrine->persist($document); // For getting ID

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);

                continue;
            }

            if ($action === InventoryChangeset::ACTION_UPDATE && $document instanceof Document) {
                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);

                continue;
            }

            throw new \RuntimeException('State mismatch between database and changeset');
        }

        $this->applyDeletes($changeset, $dossier);
    }

    private function applyDeletes(InventoryChangeset $changeset, Dossier $dossier): void
    {
        foreach ($changeset->getDeletes() as $documentNr) {
            $document = $this->loadDocumentFromDossierEntity($dossier, $documentNr);
            if (! $document instanceof Document || $dossier->getStatus() !== Dossier::STATUS_CONCEPT) {
                throw new \RuntimeException('State mismatch between database and changeset');
            }

            $this->documentUpdater->databaseRemove($document, $dossier);
        }
    }

    public function sendMessagesForChangeset(InventoryChangeset $changeset, Dossier $dossier, RunProgress $runProgress): void
    {
        if ($dossier->getStatus() === Dossier::STATUS_PUBLISHED || $dossier->getStatus() === Dossier::STATUS_PREVIEW) {
            foreach ($dossier->getInquiries() as $inquiry) {
                $this->inquiryService->generateInventory($inquiry);
                $this->inquiryService->generateArchives($inquiry);
            }
        }

        $this->messageBus->dispatch(GenerateSanitizedInventoryMessage::forDossier($dossier));
        $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));
        $this->messageBus->dispatch(UpdateDossierMessage::forDossier($dossier));

        foreach ($changeset->getAll() as $documentNr => $action) {
            $runProgress->tick();

            if ($action === InventoryChangeset::ACTION_DELETE) {
                // Since the document is already removed from the dossier entity we need to fetch it from DB
                $document = $this->loadDocumentFromDatabase($documentNr);
                if (! $document instanceof Document) {
                    throw new \RuntimeException('State mismatch between database and changeset');
                }

                $this->documentUpdater->asyncRemove($document, $dossier);
                continue;
            }

            $document = $this->loadDocumentFromDossierEntity($dossier, $documentNr);
            if (! $document instanceof Document) {
                throw new \RuntimeException('State mismatch between database and changeset');
            }

            $this->documentUpdater->asyncUpdate($document);
        }
    }

    private function loadDocumentFromDatabase(int|string $documentNr): ?Document
    {
        return $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
    }

    private function loadDocumentFromDossierEntity(Dossier $dossier, string $documentNr): ?Document
    {
        /** @var Document|false $document */
        $document = $dossier->getDocuments()->filter(
            /* @phpstan-ignore-next-line */
            function (Document $doc) use ($documentNr): bool {
                return $doc->getDocumentNr() === $documentNr;
            }
        )->first();

        return $document ?: null;
    }
}
