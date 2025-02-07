<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Status;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateType;
use App\ValueObject\DossierUploadStatus;

final readonly class UploadStatusDtoFactory
{
    public function __construct(private DocumentFileService $documentFileService)
    {
    }

    public function make(WooDecision $wooDecision, DocumentFileSet $documentFileSet): UploadStatusDto
    {
        $dossierUploadStatus = new DossierUploadStatus($wooDecision);

        return new UploadStatusDto(
            wooDecision: $wooDecision,
            dossierId: $wooDecision->getId(),
            status: $documentFileSet->getStatus(),
            canProcess: $this->documentFileService->canProcess($documentFileSet),
            uploadedFiles: $this->getUploadedFiles($documentFileSet),
            expectedDocumentsCount: $dossierUploadStatus->getExpectedUploadCount(),
            currentDocumentsCount: $dossierUploadStatus->getUploadedDocuments()->count(),
            missingDocuments: $dossierUploadStatus->getMissingDocumentIds()->getValues(),
            changes: $this->getStatus($documentFileSet),
        );
    }

    /**
     * @return list<UploadedFileDto>
     */
    private function getUploadedFiles(DocumentFileSet $documentFileSet): array
    {
        return $documentFileSet
            ->getUploads()
            ->map(static fn (DocumentFileUpload $upload): UploadedFileDto => UploadedFileDto::fromEntity($upload))
            ->getValues();
    }

    /**
     * @return array<value-of<DocumentFileUpdateType>,int> $changes
     */
    private function getStatus(DocumentFileSet $documentFileSet): array
    {
        if (! $documentFileSet->getStatus()->needsConfirmation()) {
            return [];
        }

        $changes = array_reduce(
            DocumentFileUpdateType::cases(),
            static function (array $carry, DocumentFileUpdateType $type): array {
                $carry[$type->value] = 0;

                return $carry;
            },
            [],
        );

        return $documentFileSet
            ->getUpdates()
            ->reduce(function (array $carry, DocumentFileUpdate $update): array {
                $carry[$update->getType()->value]++;

                return $carry;
            }, $changes);
    }
}
