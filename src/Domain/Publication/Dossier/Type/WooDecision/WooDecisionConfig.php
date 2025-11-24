<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\Step\StepDefinition;
use Shared\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentsStepDefinition;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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

    /**
     * @codeCoverageIgnore
     */
    public function getSecurityExpression(): ?Expression
    {
        return null;
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->wooDecisionWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return WooDecision::class;
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

    /**
     * @codeCoverageIgnore
     */
    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_woodecision_details_create';
    }

    public function getSubEntityClasses(): array
    {
        return [
            WooDecisionMainDocument::class,
            WooDecisionAttachment::class,
            Document::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::DECISION;
    }
}
