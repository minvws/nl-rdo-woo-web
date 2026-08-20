<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\DraftDecision;

use Shared\Domain\Publication\Dossier\Step\StepDefinition;
use Shared\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @codeCoverageIgnore
 */
readonly class DraftDecisionConfig implements DossierTypeConfigInterface
{
    public function __construct(
        #[Target(DossierWorkflow::DRAFT_DECISION->value)]
        private WorkflowInterface $draftDecisionWorkflow,
        private bool $hasFeatureDraftDecision,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::DRAFT_DECISION;
    }

    public function getSecurityExpression(): ?Expression
    {
        if (! $this->hasFeatureDraftDecision) {
            return new Expression('false');
        }

        return null;
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->draftDecisionWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return DraftDecision::class;
    }

    /**
     * @return array<array-key, StepDefinitionInterface>
     */
    public function getSteps(): array
    {
        return [
            StepDefinition::create($this, StepName::DETAILS),
            StepDefinition::create($this, StepName::CONTENT),
            StepDefinition::create($this, StepName::PUBLICATION),
        ];
    }

    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_draftdecision_details_create';
    }

    public function getSubEntityClasses(): array
    {
        return [
            DraftDecisionMainDocument::class,
            DraftDecisionAttachment::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::CONTENT;
    }
}
