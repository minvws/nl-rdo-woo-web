<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\RequestForAdvice;

use App\Domain\Search\Result\Dossier\AbstractDossierTypeSearchResult;
use Symfony\Component\Uid\Uuid;

readonly class RequestForAdviceSearchResult extends AbstractDossierTypeSearchResult
{
    public function __construct(
        Uuid $id,
        string $dossierNr,
        string $documentPrefix,
        public string $title,
        public ?string $summary,
        public ?\DateTimeImmutable $publicationDate,
        // This count is actually the attachment count + 1 (for the main document)
        public int $documentCount,
        public ?\DateTimeImmutable $date,
    ) {
        parent::__construct($id, $dossierNr, $documentPrefix);
    }
}
