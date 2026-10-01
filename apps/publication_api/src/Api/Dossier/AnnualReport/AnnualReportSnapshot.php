<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;

readonly class AnnualReportSnapshot
{
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
    ) {
    }

    public static function of(AnnualReport $annualReport): self
    {
        return new self(MetadataSnapshot::ofNullable($annualReport->getMainDocument()));
    }
}
