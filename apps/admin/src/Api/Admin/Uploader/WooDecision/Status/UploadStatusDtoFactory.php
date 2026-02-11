<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Uploader\WooDecision\Status;

use ArrayObject;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

use function array_reduce;

final readonly class UploadStatusDtoFactory
{
    public function __construct(private DocumentFileService $documentFileService)
    {
    }

    public function make(WooDecision $wooDecision, DocumentFileSet $documentFileSet): UploadStatusDto
    {
        $dossierUploadStatus = $wooDecision->getUploadStatus();

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
     * @return ArrayObject<value-of<DocumentFileUpdateType>,int> $changes
     */
    private function getStatus(DocumentFileSet $documentFileSet): ArrayObject
    {
        if (! $documentFileSet->getStatus()->needsConfirmation()) {
            return new ArrayObject();
        }

        /** @var array<value-of<DocumentFileUpdateType>,0> $changes */
        $changes = array_reduce(
            DocumentFileUpdateType::cases(),
            static function (array $carry, DocumentFileUpdateType $type): array {
                $carry[$type->value] = 0;

                return $carry;
            },
            [],
        );

        $result = $documentFileSet
            ->getUpdates()
            ->reduce(function (array $carry, DocumentFileUpdate $update): array {
                /** @var array<value-of<DocumentFileUpdateType>,int> $carry */
                $carry[$update->getType()->value]++;

                return $carry;
            }, $changes);

        /** @var ArrayObject<value-of<DocumentFileUpdateType>,int> */
        return new ArrayObject($result);
    }
}
