<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Status;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateType;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;

#[ApiResource()]
#[Get(
    name: 'api_uploader_woo_decision_status',
    uriTemplate: '/uploader/woo-decision/{dossierId}/status',
    security: "is_granted('AuthMatrix.dossier.update', object.wooDecision)",
    stateless: false,
    provider: UploadStatusProvider::class,
)]
final readonly class UploadStatusDto
{
    /**
     * @param array<array-key,UploadedFileDto>            $uploadedFiles
     * @param array<array-key,string>                     $missingDocuments
     * @param array<value-of<DocumentFileUpdateType>,int> $changes
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
        public array $changes,
    ) {
    }
}
