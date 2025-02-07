<?php

declare(strict_types=1);

namespace App\ValueObject;

class FilterDetails
{
    public function __construct(
        /** @var InquiryDescription[] */
        protected readonly array $dossierInquiries,     // Only available when results are filtered using dsi param
        /** @var InquiryDescription[] */
        protected readonly array $documentInquiries,    // Only available when results are filtered using dci param
        /** @var string[] */
        protected readonly array $dossierNumbers,       // Only available when results are filtered using dnr param
    ) {
    }

    /**
     * @return InquiryDescription[]
     */
    public function getDossierInquiries(): array
    {
        return $this->dossierInquiries;
    }

    /**
     * @return InquiryDescription[]
     */
    public function getDocumentInquiries(): array
    {
        return $this->documentInquiries;
    }

    /**
     * @return string[]
     */
    public function getDossierNumbers(): array
    {
        return $this->dossierNumbers;
    }
}
