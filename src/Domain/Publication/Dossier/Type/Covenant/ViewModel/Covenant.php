<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class Covenant
{
    use CommonDossierPropertiesAccessors;

    /**
     * @param non-empty-list<string> $parties
     */
    public function __construct(
        private CommonDossierProperties $commonDossier,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
        public string $previousVersionLink,
        public array $parties,
    ) {
    }
}
