<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow\Step;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\StepName;

interface StepInterface
{
    public function isCompleted(Dossier $dossier): bool;

    public function getConceptEditPath(): string;

    public function getEditPath(): string;

    public function getStepName(): StepName;
}
