<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\AnnualReport;

use App\Domain\Search\Result\Dossier\DossierTypeSearchResultInterface;

readonly class AnnualReportSearchResult implements DossierTypeSearchResultInterface
{
    public string $year;

    public function __construct(
        public string $dossierNr,
        public string $documentPrefix,
        public string $title,
        public string $summary,
        public \DateTimeImmutable $publicationDate,
        // This count is actually the attachment count + 1 (for the main document)
        public int $documentCount,
        \DateTimeImmutable $dateFrom,
    ) {
        $this->year = $dateFrom->format('Y');
    }
}
