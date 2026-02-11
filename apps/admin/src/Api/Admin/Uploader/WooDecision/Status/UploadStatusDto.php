<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Uploader\WooDecision\Status;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ArrayObject;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            name: 'api_uploader_woo_decision_status',
            uriTemplate: '/uploader/woo-decision/{dossierId}/status',
            security: "is_granted('AuthMatrix.dossier.update', object.wooDecision)",
            stateless: false,
            provider: UploadStatusProvider::class,
        ),
    ],
)]
final readonly class UploadStatusDto
{
    /**
     * @param array<array-key,UploadedFileDto> $uploadedFiles
     * @param array<array-key,string> $missingDocuments
     * @param ArrayObject<value-of<DocumentFileUpdateType>,int> $changes
     */
    public function __construct(
        #[Ignore]
        public WooDecision $wooDecision,
        #[ApiProperty(identifier: true)]
        public Uuid $dossierId,
        public DocumentFileSetStatus $status,
        public bool $canProcess,
        public array $uploadedFiles,
        public int $expectedDocumentsCount,
        public int $currentDocumentsCount,
        public array $missingDocuments,
        #[Context([Serializer::EMPTY_ARRAY_AS_OBJECT => true])]
        public ArrayObject $changes,
    ) {
    }
}
