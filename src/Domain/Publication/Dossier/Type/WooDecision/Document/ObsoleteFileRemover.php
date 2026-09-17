<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;

readonly class ObsoleteFileRemover
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
    ) {
    }

    public function removeIfObsolete(Document $document): void
    {
        if ($document->shouldBeUploaded()) {
            return;
        }

        $this->entityStorageService->deleteAllFilesForEntity($document);
        $this->thumbStorage->deleteAllThumbsForEntity($document);
        $document->getFileInfo()->removeFileProperties();
    }
}
