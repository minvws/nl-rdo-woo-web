<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class OtherPublication
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public \DateTimeImmutable $date,
    ) {
    }
}
