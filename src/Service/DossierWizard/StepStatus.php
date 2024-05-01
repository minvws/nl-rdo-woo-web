<?php

declare(strict_types=1);

namespace App\Service\DossierWizard;

use App\Domain\Publication\Dossier\Step\StepName;

class StepStatus
{
    public function __construct(
        private readonly StepName $step,
        private readonly bool $completed,
        private readonly string $routeName,
        private readonly string $conceptRouteName,
        private readonly string $editRouteName,
        private readonly bool $beforeCurrentStep,
        private readonly bool $accessible,
    ) {
    }

    public function getStepName(): StepName
    {
        return $this->step;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getConceptRouteName(): string
    {
        return $this->conceptRouteName;
    }

    public function getEditRouteName(): string
    {
        return $this->editRouteName;
    }

    public function isBeforeCurrentStep(): bool
    {
        return $this->beforeCurrentStep;
    }

    public function isAccessible(): bool
    {
        return $this->accessible;
    }
}
