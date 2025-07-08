<?php

declare(strict_types=1);

namespace App\Domain\Upload\Exception;

use App\Domain\Upload\UploadEntity;
use App\Domain\Upload\UploadStatus;
use Symfony\Component\Uid\Uuid;

class UploadException extends \RuntimeException
{
    public static function forInvalidStatusUpdate(UploadEntity $uploadEntity, UploadStatus $targetStatus): self
    {
        return new self(sprintf(
            'Cannot update UploadEntity %s status from %s to %s',
            $uploadEntity->getId(),
            $uploadEntity->getStatus()->value,
            $targetStatus->value,
        ));
    }

    public static function forCannotUpload(UploadEntity $uploadEntity): self
    {
        return new self(sprintf(
            'Cannot process upload for UploadEntity %s with upload id %s and status %s',
            $uploadEntity->getId(),
            $uploadEntity->getUploadId(),
            $uploadEntity->getStatus()->value,
        ));
    }

    public static function forNotAllowed(): self
    {
        return new self('Upload not allowed');
    }

    public static function forCannotDownload(UploadEntity $uploadEntity): self
    {
        return new self(sprintf(
            'Cannot download UploadEntity %s with upload id %s and status %s',
            $uploadEntity->getId(),
            $uploadEntity->getUploadId(),
            $uploadEntity->getStatus()->value,
        ));
    }

    public static function forEntityNotFound(Uuid $uuid): self
    {
        return new self(sprintf(
            'No UploadEntity found with uuid %s',
            $uuid,
        ));
    }

    public static function forEntityNotFoundByUploadId(string $uploadId): self
    {
        return new self(sprintf(
            'No UploadEntity found with uploadId %s',
            $uploadId,
        ));
    }
}
