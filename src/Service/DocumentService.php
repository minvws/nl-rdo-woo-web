<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Exception\DocumentReplaceException;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\Storage\EntityStorageService;
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
readonly class DocumentService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private SubTypeIngester $ingester,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
        private SubTypeIndexer $subTypeIndexer,
        private MessageBusInterface $messageBus,
        private HistoryService $historyService,
        private DocumentNumberExtractor $documentNumberExtractor,
        private DocumentDispatcher $documentDispatcher,
    ) {
    }

    public function removeDocumentFromDossier(WooDecision $dossier, Document $document, bool $flush = true): void
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

            $this->subTypeIndexer->remove($document);
        } else {
            $this->subTypeIndexer->index($document);
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

        $this->ingester->ingest($document, new IngestProcessOptions());

        foreach ($document->getDossiers() as $dossier) {
            $this->messageBus->dispatch(
                UpdateDossierArchivesMessage::forDossier($dossier)
            );
        }
    }

    private function removeAllFilesForDocument(Document $document): void
    {
        $this->entityStorageService->deleteAllFilesForEntity($document);

        $this->thumbStorage->deleteAllThumbsForEntity($document);
    }

    public function replace(WooDecision $dossier, Document $document, UploadedFile $uploadedFile): void
    {
        $filename = $uploadedFile->getClientOriginalName();
        try {
            $fileDocumentNr = $this->documentNumberExtractor->extract($filename, $dossier);
            if ($fileDocumentNr !== $document->getDocumentId()) {
                throw DocumentReplaceException::forFilenameMismatch($document, $filename);
            }
        } catch (\RuntimeException) {
            throw DocumentReplaceException::forFilenameMismatch($document, $filename);
        }

        $remotePath = '/uploads/' . $dossier->getId() . '/' . $uploadedFile->getClientOriginalName();
        if (! $this->entityStorageService->store($uploadedFile, $remotePath)) {
            throw new \RuntimeException('Document file replacements failed, could not store uploadedfile');
        }

        $this->documentDispatcher->dispatchReplaceDocumentCommand(
            dossierId: $dossier->getId(),
            documentId: $document->getId(),
            remotePath: $remotePath,
            originalFilename: $uploadedFile->getClientOriginalName(),
        );

        $this->historyService->addDocumentEntry($document, 'document_replaced', [
            'filetype' => $document->getFileInfo()->getType(),
            'filesize' => Utils::getFileSize($document),
        ]);
    }
}
