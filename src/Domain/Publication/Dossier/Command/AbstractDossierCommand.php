<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Command;

use Shared\Domain\Publication\Dossier\AbstractDossier;

abstract readonly class AbstractDossierCommand
{
    public function __construct(
        public AbstractDossier $dossier,
    ) {
    }
}
