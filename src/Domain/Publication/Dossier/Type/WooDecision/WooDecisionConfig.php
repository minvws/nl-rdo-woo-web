<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\DocumentsStepDefinition;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class WooDecisionConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $wooDecisionWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::WOO_DECISION;
    }

    public function getSecurityExpression(): ?Expression
    {
        return null;
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->wooDecisionWorkflow;
    }

    public function createInstance(): AbstractDossier
    {
        $dossier = new WooDecision();
        $dossier->setPublicationReason(PublicationReason::getDefault());

        return $dossier;
    }

    /**
     * @return StepDefinitionInterface[]
     */
    public function getSteps(): array
    {
        return [
            StepDefinition::create($this, StepName::DETAILS),
            StepDefinition::create($this, StepName::DECISION),
            DocumentsStepDefinition::create($this, StepName::DOCUMENTS),
            StepDefinition::create($this, StepName::PUBLICATION),
        ];
    }

    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_woodecision_details_create';
    }
}