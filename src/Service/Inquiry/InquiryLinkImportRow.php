<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\ValueObject\DocumentNumber;

final readonly class InquiryLinkImportRow
{
    /**
     * @param array<array-key, string> $inquiryNumbers
     */
    public function __construct(
        public DocumentNumber $documentNumber,
        public array $inquiryNumbers,
    ) {
    }
}
