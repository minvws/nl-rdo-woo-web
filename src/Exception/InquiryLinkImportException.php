<?php

declare(strict_types=1);

namespace App\Exception;

class InquiryLinkImportException extends TranslatableException
{
    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            "Document $documentNr does not exist",
            'Document {documentNr} does not exist',
            [
                '{documentNr}' => $documentNr,
            ]
        );
    }
}
