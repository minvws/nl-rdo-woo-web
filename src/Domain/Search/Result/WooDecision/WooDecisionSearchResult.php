<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Search\Result\DossierTypeSearchResultInterface;

class WooDecisionSearchResult implements DossierTypeSearchResultInterface
{
    public function __construct(
        public readonly string $dossierNr,
        public readonly string $documentPrefix,
        public readonly string $title,
        public readonly DecisionType $decision,
        public readonly string $summary,
        public readonly \DateTimeImmutable $publicationDate,
        public readonly \DateTimeImmutable $decisionDate,
        public readonly int $documentCount,
    ) {
    }
}
