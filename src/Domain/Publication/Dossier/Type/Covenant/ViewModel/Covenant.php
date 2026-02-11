<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class Covenant
{
    use CommonDossierPropertiesAccessors;

    /**
     * @param non-empty-list<string> $parties
     */
    public function __construct(
        private CommonDossierProperties $commonDossier,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public string $previousVersionLink,
        public array $parties,
    ) {
    }
}
