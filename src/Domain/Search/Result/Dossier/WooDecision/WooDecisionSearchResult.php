<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\WooDecision;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Search\Result\Dossier\AbstractDossierTypeSearchResult;
use Symfony\Component\Uid\Uuid;

readonly class WooDecisionSearchResult extends AbstractDossierTypeSearchResult
{
    public function __construct(
        Uuid $id,
        string $dossierNr,
        string $documentPrefix,
        public string $title,
        public ?DecisionType $decision,
        public ?string $summary,
        public ?DateTimeImmutable $publicationDate,
        public ?DateTimeImmutable $decisionDate,
        public ?int $documentCount,
        public PublicationReason $publicationReason,
    ) {
        parent::__construct($id, $dossierNr, $documentPrefix);
    }
}
