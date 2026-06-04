<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;
use Shared\ValueObject\PlainDate;

final readonly class Covenant
{
    use CommonDossierPropertiesAccessors;

    /**
     * @param non-empty-list<string> $parties
     */
    public function __construct(
        private CommonDossierProperties $commonDossier,
        public ?PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public string $previousVersionLink,
        public array $parties,
    ) {
    }
}
