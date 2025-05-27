<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Search\Result\Dossier\AbstractDossierTypeSearchResult;
use Symfony\Component\Uid\Uuid;

readonly class WooDecisionSearchResult extends AbstractDossierTypeSearchResult
{
    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        Uuid $id,
        string $dossierNr,
        string $documentPrefix,
        public string $title,
        public ?DecisionType $decision,
        public ?string $summary,
        public ?\DateTimeImmutable $publicationDate,
        public ?\DateTimeImmutable $decisionDate,
        public ?int $documentCount,
        public PublicationReason $publicationReason,
    ) {
        parent::__construct($id, $dossierNr, $documentPrefix);
    }
}
