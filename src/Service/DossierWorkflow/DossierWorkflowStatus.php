<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

use App\Entity\Dossier;
use Symfony\Component\Uid\Uuid;

class DossierWorkflowStatus
{
    public function __construct(
        private readonly StepName $currentStep,
        private readonly ?Uuid $dossierId,
        /** @var StepStatus[] $steps */
        private readonly array $steps,
        private readonly string $status,
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
        return $this->dossierId && $this->status === Dossier::STATUS_CONCEPT;
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
            && ($this->status === Dossier::STATUS_CONCEPT
                || $this->status === Dossier::STATUS_SCHEDULED
                || $this->status === Dossier::STATUS_PREVIEW);
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
        return $this->status === Dossier::STATUS_PUBLISHED
            || $this->status === Dossier::STATUS_PREVIEW;
    }

    private function getStep(StepName $stepName): StepStatus
    {
        if (! array_key_exists($stepName->value, $this->steps)) {
            throw new \OutOfBoundsException('No workflow status defined for step ' . $stepName->value);
        }

        return $this->steps[$stepName->value];
    }

    /**
     * @return string[]
     */
    public function getAllowedStatusUpdates(): array
    {
        if ($this->status === Dossier::STATUS_CONCEPT) {
            return [
                Dossier::STATUS_SCHEDULED,
                Dossier::STATUS_PREVIEW,
                Dossier::STATUS_PUBLISHED,
            ];
        }

        if ($this->status === Dossier::STATUS_PREVIEW) {
            return [
                Dossier::STATUS_PUBLISHED,
            ];
        }

        return [];
    }
}
