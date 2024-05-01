<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Ingest\IngestDossierMessage;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\IndexDossierMessage;
use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\InventoryProcessRun;
use App\Exception\ProcessInventoryException;
use App\Message\GenerateSanitizedInventoryMessage;
use App\Message\IngestDecisionMessage;
use App\Message\InventoryProcessRunMessage;
use App\Message\RemoveInventoryAndDocumentsMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\DossierWizard\WizardStatusFactory;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use App\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class handles dossier management.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
readonly class DossierService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private InquirySessionService $inquirySession,
        private DocumentStorageService $documentStorage,
        private WizardStatusFactory $statusFactory,
        private HistoryService $historyService,
    ) {
    }

    /**
     * @todo This is to be refactored out of this service as part of #2066
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function updateHistory(AbstractDossier $dossier): void
    {
        $oldDossier = $this->doctrine->getUnitOfWork()->getOriginalEntityData($dossier);
        if (empty($oldDossier)) {
            // There is no old dossier, so this is probably not an update, but a create.
            return;
        }

        $changes = [];
        if ($dossier instanceof WooDecision && $oldDossier['decisionDate'] != $dossier->getDecisionDate()) {
            $changes[] = 'decision_date';
        }
        if ($oldDossier['title'] !== $dossier->getTitle()) {
            $changes[] = 'title';
        }
        if ($oldDossier['summary'] !== $dossier->getSummary()) {
            $changes[] = 'summary';
        }

        if ($changes) {
            // All changes are translated
            foreach ($changes as $key => $value) {
                $changes[$key] = '%history.value.' . $value . '%';
            }

            $this->historyService->addDossierEntry($dossier, 'dossier_updated', ['changes' => $changes]);
        }

        if ($oldDossier['publicationDate'] != $dossier->getPublicationDate()) {
            $this->historyService->addDossierEntry(
                $dossier,
                'dossier_update_publication_date',
                [
                    'date' => $dossier->getPublicationDate()?->format('d-m-Y'),
                ],
            );
        }

        if ($dossier instanceof DossierTypeWithPreview && $oldDossier['previewDate'] != $dossier->getPreviewDate()) {
            $this->historyService->addDossierEntry(
                $dossier,
                'dossier_update_preview_date',
                [
                    'date' => $dossier->getPreviewDate()?->format('d-m-Y'),
                ],
            );
        }
    }

    public function updateDecision(Dossier $dossier): void
    {
        $this->updateHistory($dossier);
        $this->handleEntityUpdate($dossier);

        // If the dossier no longer needs inventory and documents: remove them
        if (! $dossier->needsInventoryAndDocuments()) {
            $this->messageBus->dispatch(
                RemoveInventoryAndDocumentsMessage::forDossier($dossier)
            );
        }
    }

    public function ingest(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(new IngestDossierMessage($dossier->getId()));
    }

    public function update(Dossier $dossier): void
    {
        if ($dossier->getStatus()->isNewOrConcept()) {
            return;
        }

        $this->messageBus->dispatch(IndexDossierMessage::forDossier($dossier));
    }

    public function generateSanitizedInventory(Dossier $dossier): void
    {
        $this->messageBus->dispatch(GenerateSanitizedInventoryMessage::forDossier($dossier));
    }

    public function generateArchives(Dossier $dossier): void
    {
        if ($dossier->getStatus()->isNewOrConcept()) {
            return;
        }

        $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));
    }

    /**
     * Store the decision document to disk and add it to the dossier.
     */
    public function updateDecisionDocument(UploadedFile $upload, WooDecision $dossier): void
    {
        $this->logger->info('uploaded decision file', [
            'path' => $upload->getRealPath(),
            'original_file' => $upload->getClientOriginalName(),
            'size' => $upload->getSize(),
            'file_hash' => hash_file('sha256', $upload->getRealPath()),
        ]);

        $isUpdate = true;

        $decisionDocument = $dossier->getDecisionDocument();
        if ($decisionDocument === null) {
            $isUpdate = false;
            $decisionDocument = new DecisionDocument();
            $dossier->setDecisionDocument($decisionDocument);
            $decisionDocument->setDossier($dossier);
        }

        $fileInfo = $decisionDocument->getFileInfo();

        // If there was already a decision file: clean it up
        if ($fileInfo->isUploaded()) {
            $this->documentStorage->removeFileForEntity($decisionDocument);
            $fileInfo->removeFileProperties();
        }

        $fileInfo->setSourceType(SourceType::SOURCE_PDF);
        $fileInfo->setType('pdf');
        $fileInfo->setName($upload->getClientOriginalName());

        $this->doctrine->persist($decisionDocument);

        if (! $this->documentStorage->storeDocument($upload, $decisionDocument)) {
            throw new \RuntimeException('Could not store decision document');
        }

        $this->validateCompletion($dossier);

        if ($isUpdate) {
            $this->historyService->addDossierEntry($dossier, 'dossier_update_decision', [
                'filetype' => $fileInfo->getType(),
                'filename' => $upload->getClientOriginalName(),
                'filesize' => Utils::getFileSize($decisionDocument),
            ]);
        }

        $this->messageBus->dispatch(
            IngestDecisionMessage::forDossier($dossier)
        );
    }

    // Returns true when the dossier (and/or document) is allowed to be viewed. This will also
    // consider documents and dossiers which are marked as preview and that are allowed by the session.
    public function isViewingAllowed(AbstractDossier $dossier, ?Document $document = null): bool
    {
        // If dossier is published, allow viewing
        if ($dossier->getStatus()->isPublished()) {
            return true;
        }

        // If dossier is not preview, deny access
        if (! $dossier->getStatus()->isPreview()) {
            return false;
        }

        if (! $dossier instanceof WooDecision) {
            return false;
        }

        $inquiryIds = $this->inquirySession->getInquiries();

        // Check if any inquiry id from the dossier is in the session inquiry ids.
        foreach ($dossier->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        // If dossier is not visible, and no document is given, deny viewing
        if (! $document) {
            return false;
        }

        // Check all inquiry ids from the document to see if we have one matching in our session.
        foreach ($document->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        return false;
    }

    public function processInventory(UploadedFile $upload, Dossier $dossier): InventoryProcessRun
    {
        // First cleanup any old process run
        $processRun = $dossier->getProcessRun();
        if ($processRun) {
            if ($processRun->isNotFinal()) {
                throw new \RuntimeException();
            }

            $this->documentStorage->removeFileForEntity($processRun);
            $this->doctrine->remove($processRun);
            $this->doctrine->flush();
        }

        // Now create the new run
        $run = new InventoryProcessRun($dossier);

        $file = $run->getFileInfo();
        $file->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $file->setType('pdf');
        $file->setName($upload->getClientOriginalName());

        $this->doctrine->persist($run);
        $this->doctrine->flush();

        if (! $this->documentStorage->storeDocument($upload, $run)) {
            $this->logger->error('Could not store the inventory spreadsheet.', [
                'dossier' => $dossier->getId()->toRfc4122(),
                'filename' => $upload->getClientOriginalName(),
            ]);

            $run->addGenericException(ProcessInventoryException::forInventoryCannotBeStored());
            $run->fail();

            $this->doctrine->persist($run);
            $this->doctrine->flush();

            throw new \RuntimeException('Could not store the inventory upload');
        }

        $this->messageBus->dispatch(
            new InventoryProcessRunMessage($run->getId())
        );

        return $run;
    }

    public function confirmInventoryUpdate(Dossier $dossier): void
    {
        $run = $dossier->getProcessRun();
        if (! $run) {
            throw new \RuntimeException('There is no run to confirm for this dossier');
        }

        $run->confirm();

        $this->doctrine->persist($run);
        $this->doctrine->flush();

        $fileInfo = $run->getFileInfo();
        $this->historyService->addDossierEntry($dossier, 'dossier_update_inventory', [
            'filetype' => $fileInfo->getType(),
            'filename' => $fileInfo->getName(),
            'filesize' => Utils::getFileSize($run),
        ]);

        $this->messageBus->dispatch(
            new InventoryProcessRunMessage($run->getId())
        );
    }

    public function rejectInventoryUpdate(Dossier $dossier): void
    {
        $run = $dossier->getProcessRun();
        if (! $run) {
            throw new \RuntimeException('There is no run to reject for this dossier');
        }

        $run->reject();

        $this->doctrine->persist($run);
        $this->doctrine->flush();
    }

    /**
     * Validate dossier completion and set dossier completed flag.
     */
    public function validateCompletion(AbstractDossier $dossier, bool $flush = true): bool
    {
        $completed = $this->statusFactory->getWizardStatus($dossier, StepName::DETAILS)->isCompleted();

        $dossier->setCompleted($completed);
        $this->doctrine->persist($dossier);

        if ($flush) {
            $this->doctrine->flush();
        }

        return $completed;
    }

    public function updateDetails(AbstractDossier $dossier): void
    {
        $this->updateHistory($dossier);
        $this->handleEntityUpdate($dossier);
    }

    public function handleEntityUpdate(AbstractDossier $dossier): void
    {
        if ($dossier->getStatus() === DossierStatus::DELETED) {
            return;
        }

        $this->validateCompletion($dossier);

        $this->messageBus->dispatch(
            IndexDossierMessage::forDossier($dossier)
        );
    }
}
