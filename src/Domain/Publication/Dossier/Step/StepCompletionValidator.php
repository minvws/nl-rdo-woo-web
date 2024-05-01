<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This validator checks the Assert attributes in the given Dossier instance for a specific step (=validation group).
 */
readonly class StepCompletionValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function isCompleted(StepDefinitionInterface $step, AbstractDossier $dossier): bool
    {
        $errors = $this->validator->validate(
            $dossier,
            null,
            $step->getName()->value,
        );

        return count($errors) === 0;
    }
}
