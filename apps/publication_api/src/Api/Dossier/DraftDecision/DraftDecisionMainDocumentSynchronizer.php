<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;

readonly class DraftDecisionMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
    ) {
    }

    public function create(DraftDecision $draftDecision, DraftDecisionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($draftDecision, DraftDecisionMainDocumentRequestMapper::create($draftDecision, $mainDocumentRequestDto));
    }

    public function update(DraftDecision $draftDecision, DraftDecisionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $draftDecision->getMainDocument() !== null
            ? DraftDecisionMainDocumentRequestMapper::update($draftDecision, $mainDocumentRequestDto)
            : DraftDecisionMainDocumentRequestMapper::create($draftDecision, $mainDocumentRequestDto);

        $this->apply($draftDecision, $mainDocument);
    }

    private function apply(DraftDecision $draftDecision, DraftDecisionMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $draftDecision->setMainDocument($mainDocument);
    }
}
