<?php

declare(strict_types=1);

namespace App\Exception;

class InquiryLinkImportException extends TranslatableException
{
    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            "Document $documentNr does not exist",
            'public.global.no_doc_number',
            [
                '{documentNr}' => $documentNr,
            ]
        );
    }

    public static function forOrganisationMismatch(): self
    {
        return new self(
            'Cannot link to prefixes outside of the active organisation',
            'publication.dossier.error.opening_inventory_file',
        );
    }

    /**
     * @param string[] $caseNumberValues
     */
    public static function forInvalidCaseNumber(int $rowNumber, array $caseNumberValues): self
    {
        return new self(
            'Invalid casenumbers value(s): ' . implode(', ', $caseNumberValues),
            'publication.inquiry.error.casenumbers_invalid',
            [
                '{rownumber}' => (string) $rowNumber,
            ],
        );
    }
}
