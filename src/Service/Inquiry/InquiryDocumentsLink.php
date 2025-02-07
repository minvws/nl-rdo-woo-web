<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

class InquiryDocumentsLink
{
    /**
     * @param string[] $caseNrs
     */
    public function __construct(
        private readonly string $documentNr,
        private readonly array $caseNrs,
    ) {
    }

    public function getDocumentNr(): string
    {
        return $this->documentNr;
    }

    /**
     * @return string[]
     */
    public function getCaseNrs(): array
    {
        return $this->caseNrs;
    }
}
