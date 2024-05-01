<?php

declare(strict_types=1);

namespace App\ViewModel;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant as CovenantEntity;

final readonly class Covenant
{
    public function __construct(
        public CovenantEntity $entity,
        public string $dossierId,
        public string $dossierNr,
        public bool $isPreview,
        public string $title,
        public string $pageTitle,
        public \DateTimeImmutable $publicationDate,
        public string $summary,
    ) {
    }
}
