<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Covenant;

use App\Domain\Search\Result\DossierTypeSearchResultInterface;

readonly class CovenantSearchResult implements DossierTypeSearchResultInterface
{
    public function __construct(
        public string $dossierNr,
        public string $documentPrefix,
        public string $title,
        public string $summary,
        public \DateTimeImmutable $publicationDate,
        // This count is actually the attachment count + 1 (for the main covenant document)
        public int $documentCount,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
    ) {
    }
}
