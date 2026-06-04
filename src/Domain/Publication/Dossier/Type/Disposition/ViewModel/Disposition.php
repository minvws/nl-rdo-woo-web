<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;
use Shared\ValueObject\PlainDate;

final readonly class Disposition
{
    use CommonDossierPropertiesAccessors;

    public function __construct(
        private CommonDossierProperties $commonDossier,
        public PlainDate $date,
    ) {
    }
}
