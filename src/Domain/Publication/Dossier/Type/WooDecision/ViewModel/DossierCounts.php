<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

readonly class DossierCounts
{
    public function __construct(
        private int $totalDocumentCount,
        private int $publicDocumentCount,
    ) {
    }

    public function getTotalDocumentCount(): int
    {
        return $this->totalDocumentCount;
    }

    public function hasDocuments(): bool
    {
        return $this->totalDocumentCount > 0;
    }

    public function getPublicDocumentCount(): int
    {
        return $this->publicDocumentCount;
    }
}
