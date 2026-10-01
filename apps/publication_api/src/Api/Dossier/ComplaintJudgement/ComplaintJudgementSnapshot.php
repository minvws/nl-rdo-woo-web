<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;

readonly class ComplaintJudgementSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(ComplaintJudgement $complaintJudgement): self
    {
        return new self(MetadataSnapshot::ofNullable($complaintJudgement->getMainDocument()));
    }
}
