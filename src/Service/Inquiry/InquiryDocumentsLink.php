<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\ValueObject\DocumentNumber;

class InquiryDocumentsLink
{
    /**
     * @param array<array-key, string> $inquiryNumbers
     */
    public function __construct(
        private readonly DocumentNumber $documentNumber,
        private readonly array $inquiryNumbers,
    ) {
    }

    public function getDocumentNumber(): DocumentNumber
    {
        return $this->documentNumber;
    }

    /**
     * @return array<array-key, string>
     */
    public function getInquiryNumbers(): array
    {
        return $this->inquiryNumbers;
    }
}
