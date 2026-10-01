<?php

declare(strict_types=1);

namespace Shared\Exception;

use function implode;

class InquiryLinkImportException extends TranslatableException
{
    public static function forMissingDocument(string $documentNumber): self
    {
        return new self(
            "Document $documentNumber does not exist",
            'public.global.no_document_number',
            [
                '{documentNumber}' => $documentNumber,
            ],
        );
    }

    public static function forMatterAndPublicationContextCombination(): self
    {
        return new self(
            'A spreadsheet with a "Publicatiecontext" column cannot contain a "Matter" column',
            'publication.inquiry.error.matter_and_publication_context_combination',
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
