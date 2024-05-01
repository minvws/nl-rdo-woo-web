<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\AbstractDossier;

interface StepDefinitionInterface
{
    public function getName(): StepName;

    public function isCompleted(AbstractDossier $dossier): bool;

    public function getConceptEditRouteName(): string;

    public function getEditRouteName(): string;
}
