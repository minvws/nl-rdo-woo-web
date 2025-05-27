<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\Status;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Domain\Uploader\Exception\UploadNotFoundException;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadStatus;
use Symfony\Component\Uid\Uuid;

#[ApiResource()]
#[Get(
    uriTemplate: '/uploader/upload/{uploadId}/status',
    stateless: false,
    exceptionToStatus: [
        UploadNotFoundException::class => 404,
    ],
    security: 'user.getId() === object.userId',
    name: 'api_uploader_status',
    provider: UploadStatusProvider::class,
)]
readonly class UploadStatusDto
{
    public function __construct(
        public string $uploadId,
        public UploadStatus $status,
        #[ApiProperty(readable: false)]
        public Uuid $userId,
    ) {
    }

    public static function fromEntity(UploadEntity $entity): self
    {
        return new self(
            $entity->getUploadId(),
            $entity->getStatus(),
            $entity->getUser()->getId(),
        );
    }
}
