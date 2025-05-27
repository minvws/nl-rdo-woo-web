<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Exception;

use App\Domain\Uploader\UploadEntity;

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
}
