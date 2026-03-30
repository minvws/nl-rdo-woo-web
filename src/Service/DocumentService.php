<?php

declare(strict_types=1);

namespace Shared\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This class handles Document entity management. Not to be confused with 'ES documents' or 'upload document' (files)!
 */
readonly class DocumentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
        private SubTypeIndexer $subTypeIndexer,
        private HistoryService $historyService,
        private ValidatorInterface $validator,
    ) {
    }

    public function removeDocumentFromDossier(WooDecision $dossier, Document $document, bool $flush = true): void
    {
        // In some cases the dossier-document relation has already been cleaned up, so check first
        if ($document->getDossiers()->contains($dossier)) {
            $dossier->removeDocument($document);
            $this->entityManager->persist($dossier);
        }

        // In any case: clean up orphaned documents completely, otherwise update ES
        if ($document->getDossiers()->isEmpty()) {
            // Remove whole document including all files, as there are no links left.
            $this->entityStorageService->deleteAllFilesForEntity($document);
            $this->thumbStorage->deleteAllThumbsForEntity($document);
            $this->entityManager->remove($document);

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
            $this->entityManager->flush();
        }
    }

    /**
     * @param list<Document> $documents
     */
    public function validateDocuments(array $documents): void
    {
        $errors = $this->validator->validate($documents);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($documents, $errors);
        }
    }
}
