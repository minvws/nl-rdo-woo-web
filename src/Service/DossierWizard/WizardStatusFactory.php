<?php

declare(strict_types=1);

namespace Shared\Service\DossierWizard;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class WizardStatusFactory
{
    public function __construct(
        private DossierTypeManager $typeManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function getWizardStatus(
        AbstractDossier $dossier,
        StepName $currentStep = StepName::DETAILS,
        bool $withAccessCheck = true,
    ): DossierWizardStatus {
        $beforeCurrentStep = true;
        $nextStepAccessible = true;

        if ($withAccessCheck) {
            $typeConfig = $this->typeManager->getConfigWithAccessCheck($dossier->getType());
        } else {
            $typeConfig = $this->typeManager->getConfig($dossier->getType());
        }

        $stepStatuses = [];
        $stepDefinition = null;
        foreach ($typeConfig->getSteps() as $step) {
            if ($step->getName() === $currentStep) {
                $stepDefinition = $step;
                $beforeCurrentStep = false;
            }

            if ($dossier->getStatus()->isNew()) {
                $status = new StepStatus(
                    $step->getName(),
                    false,
                    $step->getConceptEditRouteName(),
                    $step->getConceptEditRouteName(),
                    $step->getEditRouteName(),
                    $beforeCurrentStep,
                    $nextStepAccessible,
                );
            } else {
                $status = new StepStatus(
                    $step->getName(),
                    $step->isCompleted($dossier, $this->validator),
                    $dossier->getStatus()->isConcept() ? $step->getConceptEditRouteName() : $step->getEditRouteName(),
                    $step->getConceptEditRouteName(),
                    $step->getEditRouteName(),
                    $beforeCurrentStep,
                    $nextStepAccessible,
                );
            }

            $stepStatuses[$step->getName()->value] = $status;

            if ($dossier instanceof WooDecision) {
                $nextStepAccessible = $this->isNextStepAccessibleForWooDecision($step->getName(), $dossier);
            } elseif (! $status->isCompleted()) {
                $nextStepAccessible = false;
            }
        }

        if ($stepDefinition === null) {
            throw new \RuntimeException('No StepDefinition found for current step');
        }

        return new DossierWizardStatus(
            $dossier,
            $currentStep,
            $typeConfig->getAttachmentStepName(),
            $stepStatuses,
        );
    }

    private function isNextStepAccessibleForWooDecision(StepName $stepName, WooDecision $dossier): bool
    {
        if ($stepName !== StepName::DOCUMENTS) {
            return true;
        }

        if (! $dossier->canProvideInventory()) {
            return true;
        }

        if ($dossier->hasAllExpectedUploads()) {
            return true;
        }

        if ($dossier->hasProductionReport()) {
            return false;
        }

        return true;
    }
}
