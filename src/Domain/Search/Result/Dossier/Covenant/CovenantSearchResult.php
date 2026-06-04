<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\Covenant;

use Shared\Domain\Search\Result\Dossier\AbstractDossierTypeSearchResult;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

readonly class CovenantSearchResult extends AbstractDossierTypeSearchResult
{
    public function __construct(
        Uuid $id,
        string $dossierNr,
        string $documentPrefix,
        string $title,
        public ?string $summary,
        public ?PlainDate $publicationDate,
        // This count is actually the attachment count + 1 (for the main covenant document)
        public int $documentCount,
        public ?PlainDate $dateFrom,
        public ?PlainDate $dateTo,
    ) {
        parent::__construct($id, $dossierNr, $documentPrefix, $title);
    }
}
