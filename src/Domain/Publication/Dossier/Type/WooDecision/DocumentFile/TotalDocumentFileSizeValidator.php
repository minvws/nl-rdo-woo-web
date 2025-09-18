<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;

readonly class TotalDocumentFileSizeValidator
{
    public const int MAX_SIZE = 1024 * 1024 * 1024 * 8;

    public function exceedsMaxSizeWithUpdatesApplied(DocumentFileSet $documentFileSet): bool
    {
        $updateSizes = [];
        foreach ($documentFileSet->getUpdates() as $update) {
            $updateSizes[$update->getDocument()->getId()->toRfc4122()] = $update->getFileInfo()->getSize();
        }

        $totalSize = 0;
        foreach ($documentFileSet->getDossier()->getDocuments() as $document) {
            $docId = $document->getId()->toRfc4122();
            if (array_key_exists($docId, $updateSizes)) {
                $totalSize += $updateSizes[$docId];
                unset($updateSizes[$docId]);
            } else {
                $totalSize += $document->getFileInfo()->getSize();
            }
        }

        return $totalSize > self::MAX_SIZE;
    }
}
