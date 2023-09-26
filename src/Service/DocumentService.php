<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Exception\DocumentReplaceException;
use App\Message\IngestMetadataOnlyMessage;
use App\Message\ReplaceDocumentMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class handles Document entity management. Not to be confused with 'ES documents' or 'upload document' (files)!
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly IngestLogger $ingestLogger,
        private readonly TranslatorInterface $translator,
        private readonly IngestService $ingester,
        private readonly DocumentStorageService $documentStorage,
        private readonly ThumbnailStorageService $thumbStorage,
        private readonly ElasticService $elasticService,
        private readonly MessageBusInterface $messageBus,
        private readonly FileProcessService $fileProcessService,
    ) {
    }

    public function withdraw(Document $document, WithdrawReason $reason, string $explanation): void
    {
        $this->removeAllFilesForDocument($document);

        $document->withdraw($reason, $explanation);

        $this->doctrine->persist($document);
        $this->doctrine->flush();

        // Re-ingest the document, this will update all file metadata and overwrite (with an empty set) any existing page content.
        $this->messageBus->dispatch(
            new IngestMetadataOnlyMessage($document->getId(), true)
        );

        $this->ingestLogger->success(
            $document,
            'withdraw',
            sprintf(
                'Withdrawn with reason %s. Explanation: %s',
                $this->translator->trans($reason->value),
                $explanation
            )
        );

        foreach ($document->getDossiers() as $dossier) {
            $this->messageBus->dispatch(
                UpdateDossierArchivesMessage::forDossier($dossier)
            );
        }
    }

    public function removeDocumentFromDossier(Dossier $dossier, Document $document): void
    {
        // In some cases the dossier-document relation has already been cleaned up, so check first
        if ($document->getDossiers()->contains($dossier)) {
            $dossier->removeDocument($document);
            $this->doctrine->persist($dossier);
        }

        // In any case: clean up orphaned documents completely, otherwise update ES
        if ($document->getDossiers()->count() === 0) {
            // Remove whole document including all files, as there are no links left.
            $this->removeAllFilesForDocument($document);
            $this->doctrine->remove($document);

            $this->elasticService->removeDocument($document->getDocumentNr());
        } else {
            $this->elasticService->updateDocument($document);
        }

        $this->doctrine->flush();
    }

    public function republish(Document $document): void
    {
        $document->republish();
        $this->doctrine->persist($document);
        $this->doctrine->flush();

        $this->ingester->ingest($document, new Options());

        foreach ($document->getDossiers() as $dossier) {
            $this->messageBus->dispatch(
                UpdateDossierArchivesMessage::forDossier($dossier)
            );
        }

        $this->ingestLogger->success(
            $document,
            'republish',
            'Republished',
        );
    }

    private function removeAllFilesForDocument(Document $document): void
    {
        $this->documentStorage->deleteAllFilesForDocument($document);

        $this->thumbStorage->deleteAllThumbsForDocument($document);
    }

    public function replace(Dossier $dossier, Document $document, UploadedFile $uploadedFile): void
    {
        if ($dossier->getId() === null) {
            throw new \RuntimeException('Cannot replace document for dossier without an ID');
        }

        $filename = $uploadedFile->getClientOriginalName();
        try {
            $fileDocumentNr = $this->fileProcessService->getDocumentNumberFromFilename($filename, $dossier);
            if ($fileDocumentNr !== strval($document->getDocumentId())) {
                throw DocumentReplaceException::forFilenameMismatch($document, $filename);
            }
        } catch (\RuntimeException) {
            throw DocumentReplaceException::forFilenameMismatch($document, $filename);
        }

        $remotePath = '/uploads/' . $dossier->getId() . '/' . $uploadedFile->getClientOriginalName();
        if (! $this->documentStorage->store($uploadedFile, $remotePath)) {
            throw new \RuntimeException('Document file replacements failed, could not store uploadedfile');
        }

        $message = new ReplaceDocumentMessage(
            dossierUuid: $dossier->getId(),
            documentUuid: $document->getId(),
            remotePath: $remotePath,
            originalFilename: $uploadedFile->getClientOriginalName(),
            chunked: false,
        );

        $this->messageBus->dispatch($message);

        $this->ingestLogger->success(
            $document,
            'replace',
            'File for document has been replaced',
        );
    }
}
