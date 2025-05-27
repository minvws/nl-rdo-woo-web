<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;

readonly class WooDecisionMapper implements ElasticDossierMapperInterface
{
    public function __construct(
        private DefaultDossierMapper $defaultMapper,
    ) {
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return $dossier instanceof WooDecision;
    }

    /**
     * @param WooDecision $dossier
     */
    public function map(AbstractDossier $dossier): ElasticDocument
    {
        $defaultDocument = $this->defaultMapper->map($dossier);
        $fields = $defaultDocument->getFields();

        $fields[ElasticField::PUBLICATION_REASON->value] = $dossier->getPublicationReason();
        $fields[ElasticField::DECISION_DATE->value] = $dossier->getDecisionDate()?->format(\DateTimeInterface::ATOM);
        $fields[ElasticField::DECISION->value] = $dossier->getDecision();

        $fields[ElasticField::INQUIRY_IDS->value] = $dossier->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        $fields[ElasticField::INQUIRY_CASE_NRS->value] = $dossier->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        return new ElasticDocument(
            $defaultDocument->getId(),
            ElasticDocumentType::WOO_DECISION,
            null,
            $fields,
        );
    }
}
