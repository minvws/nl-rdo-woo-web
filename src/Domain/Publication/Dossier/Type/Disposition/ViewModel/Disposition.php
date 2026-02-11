<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition\ViewModel;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class Disposition
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public DateTimeImmutable $date,
    ) {
    }
}
