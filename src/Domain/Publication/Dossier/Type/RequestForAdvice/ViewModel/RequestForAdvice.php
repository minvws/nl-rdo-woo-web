<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\RequestForAdvice\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;
use Shared\ValueObject\PlainDate;

final readonly class RequestForAdvice
{
    use CommonDossierPropertiesAccessors;

    /**
     * @param list<string> $advisoryBodies
     */
    public function __construct(
        private CommonDossierProperties $commonDossier,
        public PlainDate $date,
        public string $link,
        public array $advisoryBodies,
    ) {
    }
}
