<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition;

use Shared\Domain\Publication\Dossier\Step\StepDefinition;
use Shared\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @codeCoverageIgnore
 */
readonly class DispositionConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $dispositionWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::DISPOSITION;
    }

    public function getSecurityExpression(): ?Expression
    {
        return null;
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->dispositionWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return Disposition::class;
    }

    /**
     * @return StepDefinitionInterface[]
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
        return 'app_admin_dossier_disposition_details_create';
    }

    public function getSubEntityClasses(): array
    {
        return [
            DispositionMainDocument::class,
            DispositionAttachment::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::CONTENT;
    }
}
