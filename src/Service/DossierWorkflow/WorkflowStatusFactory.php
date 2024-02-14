<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\Step\StepInterface;

class WorkflowStatusFactory
{
    /**
     * @var StepInterface[]
     */
    private array $steps;

    public function __construct(
        StepInterface ...$steps,
    ) {
        $this->steps = $steps;
    }

    public function getWorkflowStatus(Dossier $dossier, StepName $currentStep): DossierWorkflowStatus
    {
        $beforeCurrentStep = true;
        $nextStepAccessible = true;

        $stepStatuses = [];
        foreach ($this->steps as $step) {
            if ($step->getStepName() === $currentStep) {
                $beforeCurrentStep = false;
            }

            if ($dossier->getStatus()->isNew()) {
                $status = new StepStatus(
                    $step->getStepName(),
                    false,
                    $step->getConceptEditPath(),
                    $step->getConceptEditPath(),
                    $step->getEditPath(),
                    $beforeCurrentStep,
                    $nextStepAccessible,
                );
            } else {
                $status = new StepStatus(
                    $step->getStepName(),
                    $step->isCompleted($dossier),
                    $dossier->getStatus()->isConcept() ? $step->getConceptEditPath() : $step->getEditPath(),
                    $step->getConceptEditPath(),
                    $step->getEditPath(),
                    $beforeCurrentStep,
                    $nextStepAccessible,
                );
            }

            $stepStatuses[$step->getStepName()->value] = $status;

            if (! $status->isCompleted()) {
                $nextStepAccessible = false;
            }
        }

        return new DossierWorkflowStatus(
            $currentStep,
            $stepStatuses,
            $dossier->getStatus(),
        );
    }
}
