<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer\DataProvider;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class InventoryDataProviderFactory
{
    public function __construct(
        private DocumentRepository $documentRepository,
    ) {
    }

    public function forWooDecision(WooDecision $wooDecision): WooDecisionInventoryDataProvider
    {
        return new WooDecisionInventoryDataProvider(
            $wooDecision,
            $this->documentRepository->getAllDossierDocumentsWithDossiers($wooDecision),
        );
    }

    public function forInquiry(Inquiry $inquiry): InquiryInventoryDataProvider
    {
        return new InquiryInventoryDataProvider(
            $inquiry,
            $this->documentRepository->getPublicInquiryDocumentsWithDossiers($inquiry),
        );
    }
}
