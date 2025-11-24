<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class TotalDocumentFileSizeValidator
{
    private int $maxAllowedDocumentsSizeInKib;

    public function __construct(
        #[Autowire(param: 'limit_total_document_file_size_in_gib')]
        int $maxAllowedDocumentsSizeInGib,
    ) {
        $this->maxAllowedDocumentsSizeInKib = $maxAllowedDocumentsSizeInGib * 1024 * 1024 * 1024;
    }

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

        return $totalSize > $this->maxAllowedDocumentsSizeInKib;
    }
}
