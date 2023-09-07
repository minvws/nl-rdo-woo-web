<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class handles Document entity management. Not to be confused with 'ES documents' or 'upload document' (files)!
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
    ) {
    }

    public function withdraw(Document $document, WithdrawReason $reason, string $explanation): void
    {
        $this->removeAllFilesForDocument($document);

        $document->withdraw($reason, $explanation);

        $this->doctrine->persist($document);
        $this->doctrine->flush();

        // Re-ingest the document, this will update all file metadata and overwrite (with an empty set) any existing page content.
        $this->ingester->ingest($document, new Options());

        $this->ingestLogger->success(
            $document,
            'withdraw',
            sprintf(
                'Withdrawn with reason %s. Explanation: %s',
                $this->translator->trans($reason->value),
                $explanation
            )
        );
    }

    public function removeDocumentFromDossier(Dossier $dossier, Document $document): void
    {
        if ($document->getDossiers()->contains($dossier) === false) {
            throw new \RuntimeException('Document does not belong to dossier');
        }

        $dossier->removeDocument($document);

        if ($document->getDossiers()->count() === 0) {
            // Remove whole document including all files, as there are no links left.
            $this->removeAllFilesForDocument($document);
            $this->doctrine->remove($document);
        }

        $this->doctrine->persist($dossier);
        $this->doctrine->flush();
    }

    private function removeAllFilesForDocument(Document $document): void
    {
        $this->documentStorage->deleteAllFilesForDocument($document);

        $this->thumbStorage->deleteAllThumbsForDocument($document);
    }
}
