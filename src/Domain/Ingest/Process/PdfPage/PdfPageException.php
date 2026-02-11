<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\PdfPage;

use RuntimeException;
use Shared\Domain\Publication\EntityWithFileInfo;

use function sprintf;

class PdfPageException extends RuntimeException
{
    public static function forCannotDownload(EntityWithFileInfo $entity): self
    {
        return new self(
            sprintf(
                'Cannot download entity of type %s and ID %s from storage',
                $entity::class,
                $entity->getId(),
            ),
        );
    }

    public static function forCannotExtractPage(PdfPageProcessingContext $context, string $errorMessage): self
    {
        return new self(
            sprintf(
                'Cannot extract PDF page for entity of type %s and ID %s. Pagenr: %d. Error: %s',
                $context->getEntity()::class,
                $context->getEntity()->getId(),
                $context->getPageNumber(),
                $errorMessage,
            ),
        );
    }

    public static function forCannotCreateThumbnail(PdfPageProcessingContext $context, string $errorMessage): self
    {
        return new self(
            sprintf(
                'Cannot create thumbnail of PDF page for entity of type %s and ID %s. Pagenr: %d. Error: %s',
                $context->getEntity()::class,
                $context->getEntity()->getId(),
                $context->getPageNumber(),
                $errorMessage,
            ),
        );
    }

    public static function forCannotCreateTempDir(): self
    {
        return new self('Cannot create temp dir');
    }

    public static function forLocalPageDocumentNotSet(PdfPageProcessingContext $context): self
    {
        return new self(
            sprintf(
                'No local page document set in context for entity of type %s and ID %s. Pagenr: %d',
                $context->getEntity()::class,
                $context->getEntity()->getId(),
                $context->getPageNumber(),
            ),
        );
    }
}
