<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Status;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class UploadedFileDto
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $mimeType,
    ) {
    }

    public static function fromEntity(DocumentFileUpload $upload): self
    {
        $name = $upload->getFileInfo()->getName();
        Assert::string($name);

        $mimeType = $upload->getFileInfo()->getMimeType() ?? 'application/octet-stream';

        return new self(
            id: $upload->getId(),
            name: $name,
            mimeType: $mimeType,
        );
    }
}
