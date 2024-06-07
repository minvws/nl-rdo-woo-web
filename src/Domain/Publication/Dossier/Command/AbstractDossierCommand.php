<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Command;

use App\Domain\Publication\Dossier\AbstractDossier;

abstract readonly class AbstractDossierCommand
{
    public function __construct(
        public AbstractDossier $dossier,
    ) {
    }
}
