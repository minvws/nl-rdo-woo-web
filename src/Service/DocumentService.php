<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Exception\DocumentReplaceException;
use App\Message\ReplaceDocumentMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use App\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class handles Document entity management. Not to be confused with 'ES documents' or 'upload document' (files)!
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class DocumentService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly IngestService $ingester,
        private readonly DocumentStorageService $documentStorage,
        private readonly ThumbnailStorageService $thumbStorage,
        private readonly ElasticService $elasticService,
        private readonly MessageBusInterface $messageBus,
        private readonly FileProcessService $fileProcessService,
        private readonly HistoryService $historyService,
    ) {
    }

    public function removeDocumentFromDossier(Dossier $dossier, Document $document, bool $flush = true): void
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

        $this->historyService->addDocumentEntry(
            document: $document,
            key: 'document_removed',
            context: [],
            flush: false
        );

        if ($flush) {
            $this->doctrine->flush();
        }
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
    }

    private function removeAllFilesForDocument(Document $document): void
    {
        $this->documentStorage->deleteAllFilesForDocument($document);

        $this->thumbStorage->deleteAllThumbsForDocument($document);
    }

    public function replace(Dossier $dossier, Document $document, UploadedFile $uploadedFile): void
    {
        $filename = $uploadedFile->getClientOriginalName();
        try {
            $fileDocumentNr = $this->fileProcessService->getDocumentNumberFromFilename($filename, $dossier);
            if ($fileDocumentNr !== $document->getDocumentId()) {
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

        $this->historyService->addDocumentEntry($document, 'document_replaced', [
            'filetype' => $document->getFileInfo()->getType(),
            'filesize' => Utils::getFileSize($document),
        ]);
    }
}
