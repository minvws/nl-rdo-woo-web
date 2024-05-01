<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;

interface DossierDeleteStrategyInterface
{
    public function delete(AbstractDossier $dossier): void;
}
