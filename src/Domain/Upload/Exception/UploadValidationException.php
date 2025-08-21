<?php

declare(strict_types=1);

namespace App\Domain\Upload\Exception;

use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\UploadEntity;

class UploadValidationException extends \RuntimeException
{
    public static function forInvalidMimetype(UploadEntity $uploadEntity, string $mimetype): self
    {
        return new self(sprintf(
            'Mimetype %s not allowed for group %s',
            $mimetype,
            $uploadEntity->getUploadGroupId()->value,
        ));
    }

    public static function forUnsafeFile(): self
    {
        return new self('File contains unsafe content');
    }

    public static function forCannotDetectMimetype(UploadEntity $uploadEntity): self
    {
        return new self(sprintf(
            'Mimetype cannot be determined for UploadEntity %s',
            $uploadEntity->getId(),
        ));
    }

    public static function forFilesizeExceeded(UploadEntity $uploadEntity, FileType $fileType): self
    {
        return new self(sprintf(
            'File size exceeds the allowed limit set for %s files (%d bytes). UploadEntity id: %s',
            $fileType->getTypeName(),
            $fileType->getMaxUploadSize(),
            $uploadEntity->getId(),
        ));
    }
}
