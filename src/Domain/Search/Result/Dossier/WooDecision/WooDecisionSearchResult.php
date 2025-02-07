<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Search\Result\Dossier\DossierTypeSearchResultInterface;

readonly class WooDecisionSearchResult implements DossierTypeSearchResultInterface
{
    public function __construct(
        public string $dossierNr,
        public string $documentPrefix,
        public string $title,
        public DecisionType $decision,
        public string $summary,
        public \DateTimeImmutable $publicationDate,
        public \DateTimeImmutable $decisionDate,
        public int $documentCount,
        public PublicationReason $publicationReason,
    ) {
    }
}
