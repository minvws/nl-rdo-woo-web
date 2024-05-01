<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\AbstractDossierMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Entity\Dossier;
use App\Entity\Inquiry;

readonly class WooDecisionMapper
{
    public function __construct(
        private AbstractDossierMapper $abstractDossierMapper,
    ) {
    }

    public function map(Dossier|WooDecision $dossier): ElasticDocument
    {
        $fields = $this->abstractDossierMapper->mapCommonFields($dossier);

        $fields['publication_reason'] = $dossier->getPublicationReason();
        $fields['decision_date'] = $dossier->getDecisionDate()?->format(\DateTimeInterface::ATOM);
        $fields['decision'] = $dossier->getDecision();
        $fields['inquiry_ids'] = $dossier->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        return new ElasticDocument(
            ElasticDocumentType::WOO_DECISION,
            $fields,
        );
    }
}
