<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Document;

class DocumentUpdateException extends TranslatableException
{
    public static function forNonUniqueDocument(Document $document): self
    {
        return new self(
            sprintf('Document %s already exists in another dossier', $document->getDocumentId()),
            'Document {document_id} already exists in another dossier',
            ['{document_id}' => strval($document->getDocumentId())],
        );
    }

    public static function forGenericException(\Exception $exception): self
    {
        return new self(
            $exception->getMessage(),
            'A generic document update exception occurred',
        );
    }
}
