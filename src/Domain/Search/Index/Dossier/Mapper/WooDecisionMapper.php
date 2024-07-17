<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Entity\Dossier;
use App\Entity\Inquiry;

readonly class WooDecisionMapper
{
    public function __construct(
        private DefaultDossierMapper $defaultMapper,
    ) {
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return $dossier instanceof Dossier;
    }

    /**
     * @param Dossier $dossier
     */
    public function map(AbstractDossier $dossier): ElasticDocument
    {
        $defaultDocument = $this->defaultMapper->map($dossier);
        $fields = $defaultDocument->getFields();

        $fields['publication_reason'] = $dossier->getPublicationReason();
        $fields['decision_date'] = $dossier->getDecisionDate()?->format(\DateTimeInterface::ATOM);
        $fields['decision'] = $dossier->getDecision();
        $fields['inquiry_ids'] = $dossier->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        return new ElasticDocument(
            $defaultDocument->getId(),
            ElasticDocumentType::WOO_DECISION,
            $fields,
        );
    }
}
