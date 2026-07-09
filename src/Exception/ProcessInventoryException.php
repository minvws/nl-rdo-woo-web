<?php

declare(strict_types=1);

namespace Shared\Exception;

use Exception;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

use function sprintf;
use function strval;

class ProcessInventoryException extends TranslatableException
{
    public static function forInventoryCannotBeStored(): self
    {
        return new self('publication.dossier.error.can_not_store_inventory_file');
    }

    public static function forInventoryCannotBeLoadedFromStorage(): self
    {
        return new self('publication.dossier.error.could_not_download_inventory');
    }

    public static function forMissingDocument(string $documentNumber): self
    {
        return new self(
            sprintf('Missing document %s in the inventory', $documentNumber),
            'publication.dossier.error.missing_document',
            [
                '{documentNumber}' => $documentNumber,
            ],
        );
    }

    public static function forOtherException(Exception $exception): self
    {
        return new self(
            sprintf('Uncaught exception during inventory processing: %s', $exception->getMessage()),
            'publication.dossier.error.processing_inventory',
        );
    }

    public static function forProcessingFailed(): self
    {
        return new self('Production report processing failed', 'publication.dossier.error.processing_inventory');
    }

    public static function forNoChanges(): self
    {
        return new self('publication.dossier.error.no_inventory_changes');
    }

    public static function forMaxDocumentsExceeded(int $max): self
    {
        return new self(
            'The maximum number of documents per dossier has been exceeded',
            'publication.dossier.error.max_documents_exceeded',
            [
                '{max}' => strval($max),
            ],
        );
    }

    public static function forMaxRuntimeExceeded(): self
    {
        return new self('publication.dossier.error.maximum_processing_time_exceeded');
    }

    public static function forMissingReferredDocument(string $documentNumber): self
    {
        return new self(
            sprintf('The referred document %s does not exist', $documentNumber),
            'publication.dossier.error.referred_document_does_not_exist',
            [
                '{documentNumber}' => $documentNumber,
            ],
        );
    }

    public static function forDuplicateDocumentNumber(string $documentNumber): self
    {
        return new self(
            sprintf('The document number %s is not unique within the inventory', $documentNumber),
            'publication.dossier.error.document_not_unique',
            [
                '{documentNumber}' => $documentNumber,
            ],
        );
    }

    public static function forDocumentExistsInAnotherDossier(Document $document): self
    {
        return new self(
            sprintf('Document %s already exists in another dossier', $document->getDocumentId()?->toString() ?? ''),
            'publication.dossier.error.document_already_exists',
            ['{document_id}' => $document->getDocumentId()?->toString() ?? ''],
        );
    }

    public static function forGenericRowException(Exception $exception): self
    {
        return new self($exception->getMessage(), 'publication.dossier.error.generic_document_row_exception');
    }
}
