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
