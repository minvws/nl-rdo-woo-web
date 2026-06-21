<?php

declare(strict_types=1);

namespace Shared\Exception;

use function implode;

class InquiryLinkImportException extends TranslatableException
{
    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            "Document $documentNr does not exist",
            'public.global.no_doc_number',
            [
                '{documentNr}' => $documentNr,
            ],
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
     * @param array<array-key, string> $inquiryNumberValues
     */
    public static function forInvalidInquiryNumber(int $rowNumber, array $inquiryNumberValues): self
    {
        return new self(
            'Invalid inquiry numbers value(s): ' . implode(', ', $inquiryNumberValues),
            'publication.inquiry.error.inquiryNumbers_invalid',
            [
                '{rownumber}' => (string) $rowNumber,
            ],
        );
    }
}
