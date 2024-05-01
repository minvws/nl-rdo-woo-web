<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Steps;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepCompletionValidator;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;

readonly class DecisionStepDefinition implements StepDefinitionInterface
{
    public function __construct(
        private StepCompletionValidator $validator,
    ) {
    }

    public function getName(): StepName
    {
        return StepName::DECISION;
    }

    public function isCompleted(AbstractDossier $dossier): bool
    {
        return $this->validator->isCompleted($this, $dossier);
    }

    public function getConceptEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_decision_concept';
    }

    public function getEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_decision_edit';
    }
}
