<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\PdfPage;

use RuntimeException;
use Shared\Domain\Publication\EntityWithFileInfo;

use function sprintf;

class PdfPageException extends RuntimeException
{
    public static function forCannotRead(EntityWithFileInfo $entity): self
    {
        return new self(
            sprintf(
                'Cannot read entity of type %s and ID %s from storage',
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

    public static function forPageContentsNotSet(PdfPageProcessingContext $context): self
    {
        return new self(
            sprintf(
                'No page contents set in context for entity of type %s and ID %s. Pagenr: %d',
                $context->getEntity()::class,
                $context->getEntity()->getId(),
                $context->getPageNumber(),
            ),
        );
    }
}
