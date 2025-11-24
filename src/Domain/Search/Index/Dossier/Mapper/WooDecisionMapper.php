<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier\Mapper;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Service\Inquiry\CaseNumbers;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
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

        $fields[ElasticField::INQUIRY_CASE_NRS->value] = CaseNumbers::forWooDecision($dossier)->values;

        return new ElasticDocument(
            $defaultDocument->getId(),
            ElasticDocumentType::WOO_DECISION,
            null,
            $fields,
        );
    }
}
