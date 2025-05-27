<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Api\Admin\Publication\Search\SearchResultType;
use App\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Uid\Uuid;

final readonly class AdminSearchParameters
{
    public function __construct(
        public string $searchTerm,
        public ?Uuid $dossierId = null,
        public ?DossierType $dossierType = null,
        public ?SearchResultType $resultType = null,
    ) {
    }

    public function shouldIncludeWooDecisionDocuments(): bool
    {
        if ($this->dossierType === null) {
            return true;
        }

        return $this->dossierType->isWooDecision();
    }

    public function shouldNotIncludeWooDecisionDocuments(): bool
    {
        return ! $this->shouldIncludeWooDecisionDocuments();
    }
}
