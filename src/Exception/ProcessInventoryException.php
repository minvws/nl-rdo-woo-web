<?php

declare(strict_types=1);

namespace App\Exception;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

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

    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            sprintf('Missing document %s in the inventory', $documentNr),
            'publication.dossier.error.missing_document',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forOtherException(\Exception $exception): self
    {
        return new self(
            sprintf('Uncaught exception during inventory processing: %s', $exception->getMessage()),
            'publication.dossier.error.processing_inventory',
        );
    }

    public static function forNoChanges(): self
    {
        return new self('publication.dossier.error.no_inventory_changes');
    }

    public static function forMaxRuntimeExceeded(): self
    {
        return new self('publication.dossier.error.maximum_processing_time_exceeded');
    }

    public static function forMissingReferredDocument(string $documentNr): self
    {
        return new self(
            sprintf('The referred document %s does not exist', $documentNr),
            'publication.dossier.error.referred_document_does_not_exist',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forDuplicateDocumentNr(string $documentNr): self
    {
        return new self(
            sprintf('The document number %s is not unique within the inventory', $documentNr),
            'publication.dossier.error.document_not_unique',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forDocumentExistsInAnotherDossier(Document $document): self
    {
        return new self(
            sprintf('Document %s already exists in another dossier', $document->getDocumentId() ?? ''),
            'publication.dossier.error.document_already_exists',
            ['{document_id}' => $document->getDocumentId() ?? ''],
        );
    }

    public static function forGenericRowException(\Exception $exception): self
    {
        return new self(
            $exception->getMessage(),
            'publication.dossier.error.generic_document_row_exception',
        );
    }
}
