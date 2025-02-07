<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport\ViewModel;

use App\Domain\Publication\Dossier\Type\CommonDossierPropertiesAccessors;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierProperties;

final readonly class AnnualReport
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public string $year,
    ) {
    }
}
