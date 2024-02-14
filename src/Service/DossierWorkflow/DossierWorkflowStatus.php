<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

use App\Enum\PublicationStatus;

readonly class DossierWorkflowStatus
{
    public function __construct(
        private StepName $currentStep,
        /** @var StepStatus[] $steps */
        private array $steps,
        private PublicationStatus $status,
    ) {
    }

    public function isCompleted(): bool
    {
        foreach ($this->steps as $step) {
            if (! $step->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function isConcept(): bool
    {
        return $this->status->isConcept();
    }

    public function isReadyForDecision(): bool
    {
        return $this->getStep(StepName::DETAILS)->isCompleted();
    }

    public function isReadyForDocuments(): bool
    {
        return $this->getStep(StepName::DETAILS)->isCompleted()
            && $this->getStep(StepName::DECISION)->isCompleted();
    }

    public function isReadyForPublication(): bool
    {
        return $this->getStep(StepName::DETAILS)->isCompleted()
            && $this->getStep(StepName::DECISION)->isCompleted()
            && $this->getStep(StepName::DOCUMENTS)->isCompleted()
            && ! $this->status->isPublishedOrRetracted();
    }

    public function getDetailsPath(): string
    {
        return $this->getStep(StepName::DETAILS)->getRouteName();
    }

    public function getDecisionPath(): string
    {
        return $this->getStep(StepName::DECISION)->getRouteName();
    }

    public function getDocumentsPath(): string
    {
        return $this->getStep(StepName::DOCUMENTS)->getRouteName();
    }

    public function getPublicationPath(): string
    {
        return $this->getStep(StepName::PUBLICATION)->getRouteName();
    }

    public function getFirstOpenStep(): ?StepStatus
    {
        foreach ($this->steps as $step) {
            if (! $step->isCompleted()) {
                return $step;
            }
        }

        return null;
    }

    public function getNextStep(): StepStatus
    {
        $useNext = false;
        foreach ($this->steps as $step) {
            if ($useNext === true) {
                return $step;
            }
            if ($step->getStepName() === $this->currentStep) {
                $useNext = true;
            }
        }

        $step = $this->getFirstOpenStep();
        if (! $step) {
            throw new \OutOfBoundsException('Cannot determine next dossier workflow step');
        }

        return $step;
    }

    public function getCurrentStep(): StepStatus
    {
        return $this->getStep($this->currentStep);
    }

    /**
     * @return StepStatus[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status->isPubliclyAvailable();
    }

    private function getStep(StepName $stepName): StepStatus
    {
        if (! array_key_exists($stepName->value, $this->steps)) {
            throw new \OutOfBoundsException('No workflow status defined for step ' . $stepName->value);
        }

        return $this->steps[$stepName->value];
    }
}
