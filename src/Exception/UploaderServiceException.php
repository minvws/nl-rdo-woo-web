<?php

declare(strict_types=1);

namespace Shared\Exception;

use RuntimeException;
use Shared\Domain\Publication\EntityWithFileInfo;

use function sprintf;

class UploaderServiceException extends RuntimeException
{
    public static function forCouldNotAttachFileToEntity(EntityWithFileInfo $entity): self
    {
        return new self(sprintf(
            'Could not store uploaded file for entity #%s of type %s',
            $entity->getId()->toRfc4122(),
            $entity::class,
        ));
    }

    public static function forNoFilesUploaded(string $uuid): self
    {
        return new self(sprintf(
            'No files uploaded for upload reference %s',
            $uuid,
        ));
    }

    public static function forMultipleFilesUploaded(string $uuid): self
    {
        return new self(sprintf(
            'Multiple files uploaded for upload reference %s, expecting just one',
            $uuid,
        ));
    }
}
