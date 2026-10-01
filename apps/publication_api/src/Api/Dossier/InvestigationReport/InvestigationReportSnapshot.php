<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;

readonly class InvestigationReportSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(InvestigationReport $investigationReport): self
    {
        return new self(MetadataSnapshot::ofNullable($investigationReport->getMainDocument()));
    }
}
