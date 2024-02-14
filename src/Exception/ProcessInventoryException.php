<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Document;

class ProcessInventoryException extends TranslatableException
{
    public static function forInventoryCannotBeStored(): self
    {
        return new self('Cannot store the inventory spreadsheet');
    }

    public static function forInventoryCannotBeLoadedFromStorage(): self
    {
        return new self('Could not download the inventory from document storage');
    }

    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            sprintf('Missing document %s in the inventory', $documentNr),
            'Missing document {documentNumber} in the inventory',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forOtherException(\Exception $exception): self
    {
        return new self(
            sprintf('Uncaught exception during inventory processing: %s', $exception->getMessage()),
            'Exception occurred during inventory processing',
        );
    }

    public static function forNoChanges(): self
    {
        return new self('Inventory file has no changes');
    }

    public static function forMaxRuntimeExceeded(): self
    {
        return new self('Inventory processing exceeded maximum runtime');
    }

    public static function forMissingReferredDocument(string $documentNr): self
    {
        return new self(
            sprintf('The referred document %s does not exist', $documentNr),
            'The referred document {documentNumber} does not exist',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forDuplicateDocumentNr(string $documentNr): self
    {
        return new self(
            sprintf('The document number %s is not unique within the inventory', $documentNr),
            'The document number {documentNumber} is not unique within the inventory',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forDocumentExistsInAnotherDossier(Document $document): self
    {
        return new self(
            sprintf('Document %s already exists in another dossier', $document->getDocumentId() ?? ''),
            'Document {document_id} already exists in another dossier',
            ['{document_id}' => $document->getDocumentId() ?? ''],
        );
    }

    public static function forGenericRowException(\Exception $exception): self
    {
        return new self(
            $exception->getMessage(),
            'A generic document row exception occurred',
        );
    }
}
