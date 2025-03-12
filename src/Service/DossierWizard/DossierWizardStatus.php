<?php

declare(strict_types=1);

namespace App\Service\DossierWizard;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepName;

readonly class DossierWizardStatus
{
    public function __construct(
        private AbstractDossier $dossier,
        private StepName $currentStep,
        private ?StepName $attachmentStepName,
        /** @var StepStatus[] $steps */
        private array $steps,
    ) {
    }

    public function isCurrentStepAccessibleInConceptMode(): bool
    {
        if (! $this->dossier->getStatus()->isNewOrConcept()) {
            return false;
        }

        return $this->getCurrentStep()->isAccessible();
    }

    public function isCurrentStepAccessibleInEditMode(): bool
    {
        if ($this->dossier->getStatus()->isNewOrConcept()) {
            return false;
        }

        return $this->getCurrentStep()->isAccessible();
    }

    public function getDossier(): AbstractDossier
    {
        return $this->dossier;
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

    /**
     * @deprecated use dossier status directly, it is now an enum with its own isConcept method
     */
    public function isConcept(): bool
    {
        return $this->dossier->getStatus()->isConcept();
    }

    /**
     * @deprecated use isAccessible on the StepStatus instead
     */
    public function isReadyForDecision(): bool
    {
        return $this->getStep(StepName::DETAILS)->isCompleted();
    }

    /**
     * @deprecated use isAccessible on the StepStatus instead
     */
    public function isReadyForDocuments(): bool
    {
        return $this->getStep(StepName::DETAILS)->isCompleted()
            && $this->getStep(StepName::DECISION)->isCompleted();
    }

    /**
     * @deprecated use isAccessible on the StepStatus instead
     */
    public function isReadyForPublication(): bool
    {
        $publicationStep = $this->getStep(StepName::PUBLICATION);

        return $publicationStep->isAccessible() && ! $this->dossier->getStatus()->isPublished();
    }

    public function getDetailsPath(): string
    {
        return $this->getStep(StepName::DETAILS)->getRouteName();
    }

    public function getContentStep(): StepStatus
    {
        return $this->getStep(StepName::CONTENT);
    }

    public function getContentPath(): string
    {
        return $this->getStep(StepName::CONTENT)->getRouteName();
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
        if (! $this->dossier->getStatus()->isNewOrConcept()) {
            return null;
        }

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

    /**
     * @deprecated use dossier status directly, it is now an enum with its own isPubliclyAvailable method
     */
    public function isPubliclyAvailable(): bool
    {
        return $this->dossier->getStatus()->isPubliclyAvailable();
    }

    private function getStep(StepName $stepName): StepStatus
    {
        if (! array_key_exists($stepName->value, $this->steps)) {
            throw new \OutOfBoundsException('No workflow status defined for step ' . $stepName->value);
        }

        return $this->steps[$stepName->value];
    }

    public function getAttachmentStep(): StepStatus
    {
        if ($this->attachmentStepName === null) {
            throw new \OutOfBoundsException('No attachment step defined');
        }

        return $this->getStep($this->attachmentStepName);
    }
}
