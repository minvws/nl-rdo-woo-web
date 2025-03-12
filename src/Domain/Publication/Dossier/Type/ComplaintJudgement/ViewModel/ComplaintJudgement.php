<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;

final readonly class ComplaintJudgement
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public \DateTimeImmutable $date,
    ) {
    }
}
