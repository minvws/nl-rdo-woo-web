<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice;

use Shared\Controller\Admin\Dossier\Advice\ContentStepController;
use Shared\Controller\Admin\Dossier\Advice\DetailsStepController;
use Shared\Controller\Admin\Dossier\Advice\PublicationStepController;
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
readonly class AdvicePublicationConfig implements DossierTypeConfigInterface
{
    public function __construct(
        #[Target(DossierWorkflow::ADVICE->value)]
        private WorkflowInterface $adviceWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::ADVICE;
    }

    public function getSecurityExpression(): ?Expression
    {
        return null;
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->adviceWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return Advice::class;
    }

    /**
     * @return array<array-key, StepDefinitionInterface>
     */
    public function getSteps(): array
    {
        return [
            StepDefinition::create(
                $this,
                StepName::DETAILS,
                DetailsStepController::ROUTE_NAME_CONCEPT,
                DetailsStepController::ROUTE_NAME_EDIT,
            ),
            StepDefinition::create(
                $this,
                StepName::CONTENT,
                ContentStepController::ROUTE_NAME_CONCEPT,
                ContentStepController::ROUTE_NAME_EDIT,
            ),
            StepDefinition::create(
                $this,
                StepName::PUBLICATION,
                PublicationStepController::ROUTE_NAME_CONCEPT,
                PublicationStepController::ROUTE_NAME_EDIT,
            ),
        ];
    }

    public function getCreateRouteName(): string
    {
        return DetailsStepController::ROUTE_NAME_CREATE;
    }

    public function getSubEntityClasses(): array
    {
        return [
            AdviceMainDocument::class,
            AdviceAttachment::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::CONTENT;
    }
}
