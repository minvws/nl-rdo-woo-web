<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\AnnualReport\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class AnnualReport
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public string $year,
    ) {
    }
}
