<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Step;

use RuntimeException;
use Shared\Domain\Publication\Dossier\AbstractDossier;

use function sprintf;

class StepException extends RuntimeException
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
