<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This class handles Document entity management. Not to be confused with 'ES documents' or 'upload document' (files)!
 */
readonly class DocumentService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
        private SubTypeIndexer $subTypeIndexer,
        private HistoryService $historyService,
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
        if ($document->getDossiers()->isEmpty()) {
            // Remove whole document including all files, as there are no links left.
            $this->entityStorageService->deleteAllFilesForEntity($document);
            $this->thumbStorage->deleteAllThumbsForEntity($document);
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
}
