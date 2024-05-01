<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\DecisionStepDefinition;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\DetailsStepDefinition;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\DocumentsStepDefinition;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\PublicationStepDefinition;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

readonly class WooDecisionConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $wooDecisionWorkflow,
        private DetailsStepDefinition $detailsStepDefinition,
        private DecisionStepDefinition $decisionStepDefinition,
        private DocumentsStepDefinition $documentsStepDefinition,
        private PublicationStepDefinition $publicationStepDefinition,
        private WooDecisionDeleteStrategy $deleteStrategy,
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
        $dossier->setPublicationReason(WooDecision::REASON_WOO_REQUEST);

        return $dossier;
    }

    /**
     * @return StepDefinitionInterface[]
     */
    public function getSteps(): array
    {
        return [
            $this->detailsStepDefinition,
            $this->decisionStepDefinition,
            $this->documentsStepDefinition,
            $this->publicationStepDefinition,
        ];
    }

    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_woodecision_details_create';
    }

    public function getDeleteStrategy(): DossierDeleteStrategyInterface
    {
        return $this->deleteStrategy;
    }
}
