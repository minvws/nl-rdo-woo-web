<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Exception;

use RuntimeException;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Domain\Upload\UploadEntity;

use function sprintf;

class UploadValidationException extends RuntimeException
{
    public static function forInvalidMimetype(UploadEntity $uploadEntity, string $mimetype): self
    {
        return new self(sprintf(
            'Mimetype %s not allowed for upload group %s',
            $mimetype,
            $uploadEntity->getUploadGroupId()->value,
        ));
    }

    public static function forInvalidExtension(UploadEntity $uploadEntity, string $extension): self
    {
        return new self(sprintf(
            'Extension %s not allowed for upload group %s',
            $extension,
            $uploadEntity->getUploadGroupId()->value,
        ));
    }

    public static function forMismatchBetweenExtensionAndMimetype(?FileType $fileType, string $mimetype): self
    {
        return new self(sprintf(
            'Expected a file of type %s, but got a file of mime-type %s',
            $fileType?->getTypeName() ?? 'unknown',
            $mimetype,
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
            $uploadEntity->getId()->toRfc4122(),
        ));
    }

    public static function forFilesizeExceeded(UploadEntity $uploadEntity, FileType $fileType): self
    {
        return new self(sprintf(
            'File size exceeds the allowed limit set for %s files (%d bytes). UploadEntity id: %s',
            $fileType->getTypeName(),
            $fileType->getMaxUploadSize(),
            $uploadEntity->getId()->toRfc4122(),
        ));
    }
}
