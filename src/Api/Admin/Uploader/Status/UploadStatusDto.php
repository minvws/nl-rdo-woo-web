<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Uploader\Status;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\Domain\Upload\Exception\UploadNotFoundException;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadStatus;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/balie/api/uploader/upload/{uploadId}/status',
            stateless: false,
            exceptionToStatus: [
                UploadNotFoundException::class => 404,
            ],
            security: 'user.getId() === object.userId',
            name: 'api_uploader_status',
            provider: UploadStatusProvider::class,
            openapi: new Operation(
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_TAG => ['admin'],
                ],
            ),
        ),
    ],
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
