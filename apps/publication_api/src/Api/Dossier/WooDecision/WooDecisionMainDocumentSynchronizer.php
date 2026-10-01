<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class WooDecisionMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
    ) {
    }

    public function create(WooDecision $wooDecision, WooDecisionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($wooDecision, WooDecisionMainDocumentRequestMapper::create($wooDecision, $mainDocumentRequestDto));
    }

    public function update(WooDecision $wooDecision, WooDecisionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($wooDecision, WooDecisionMainDocumentRequestMapper::update($wooDecision, $mainDocumentRequestDto));
    }

    private function apply(WooDecision $wooDecision, WooDecisionMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $wooDecision->setMainDocument($mainDocument);
    }
}
