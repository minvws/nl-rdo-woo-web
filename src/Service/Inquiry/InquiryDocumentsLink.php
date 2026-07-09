<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

class InquiryDocumentsLink
{
    /**
     * @param array<array-key, string> $inquiryNumbers
     */
    public function __construct(
        private readonly string $documentNumber,
        private readonly array $inquiryNumbers,
    ) {
    }

    public function getDocumentNumber(): string
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
