<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;

readonly class DraftDecisionSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(DraftDecision $draftDecision): self
    {
        return new self(MetadataSnapshot::ofNullable($draftDecision->getMainDocument()));
    }
}
