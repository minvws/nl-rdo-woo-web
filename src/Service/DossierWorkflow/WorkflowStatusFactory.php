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

    public function getWorkflowStatus(Dossier $dossier, ?StepName $currentStep): DossierWorkflowStatus
    {
        $stepStasuses = [];
        foreach ($this->steps as $step) {
            if (! $dossier->getId()) {
                $status = new StepStatus(
                    $step->getStepName(),
                    false,
                    $step->getConceptEditPath(),
                    $step->getConceptEditPath(),
                    $step->getEditPath(),
                );
            } else {
                $status = new StepStatus(
                    $step->getStepName(),
                    $step->isCompleted($dossier),
                    $dossier->getStatus() === Dossier::STATUS_CONCEPT ? $step->getConceptEditPath() : $step->getEditPath(),
                    $step->getConceptEditPath(),
                    $step->getEditPath(),
                );
            }

            $stepStasuses[$step->getStepName()->value] = $status;
        }

        return new DossierWorkflowStatus(
            $currentStep,
            $dossier->getId(),
            $stepStasuses,
            $dossier->getId() ? ($dossier->getStatus() ?? Dossier::STATUS_CONCEPT) : Dossier::STATUS_CONCEPT,
        );
    }
}
