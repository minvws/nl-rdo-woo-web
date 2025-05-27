<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\AbstractDossier;

class StepException extends \RuntimeException
{
    public static function forIncompatibleDossierInstance(
        StepDefinitionInterface $step,
        AbstractDossier $dossier,
    ): self {
        return new self(sprintf(
            'Dossier instance of class %s is not compatible with step %s for type %s',
            $dossier::class,
            $step->getName()->value,
            $step->getDossierType()->value,
        ));
    }
}
