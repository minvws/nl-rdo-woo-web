<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

readonly class CovenantConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $covenantWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::COVENANT;
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
        return $this->covenantWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return Covenant::class;
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

    /**
     * @codeCoverageIgnore
     */
    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_covenant_details_create';
    }

    public function getSubEntityClasses(): array
    {
        return [
            CovenantMainDocument::class,
            CovenantAttachment::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::CONTENT;
    }
}
