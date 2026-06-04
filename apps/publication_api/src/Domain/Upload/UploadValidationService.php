<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;

use function array_map;

readonly class UploadValidationService
{
    public function __construct(
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    /**
     * @return array<array-key,ConstraintViolation>
     */
    public function getValidationErrorsForUpload(Uuid $uploadId): array
    {
        $uploadEntity = $this->uploadEntityRepository->findOneBy(['uploadId' => $uploadId->toRfc4122()]);

        if (! $uploadEntity instanceof UploadEntity) {
            return [];
        }

        if ($uploadEntity->getStatus() !== UploadEntityStatus::VALIDATION_FAILED) {
            return [];
        }

        return array_map(function (string $error) {
            return new ConstraintViolation($error, '', [], null, '', null);
        }, $uploadEntity->getError() ?? []);
    }
}
