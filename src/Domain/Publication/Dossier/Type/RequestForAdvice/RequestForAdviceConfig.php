<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\RequestForAdvice;

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
readonly class RequestForAdviceConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $requestForAdviceWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::REQUEST_FOR_ADVICE;
    }

    public function getSecurityExpression(): ?Expression
    {
        // TODO restore this: return null;
        return new Expression('is_granted("ROLE_SUPER_ADMIN")');
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->requestForAdviceWorkflow;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntityClass(): string
    {
        return RequestForAdvice::class;
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
        return 'app_admin_dossier_requestforadvice_details_create';
    }

    public function getSubEntityClasses(): array
    {
        return [
            RequestForAdviceMainDocument::class,
            RequestForAdviceAttachment::class,
        ];
    }

    public function getAttachmentStepName(): ?StepName
    {
        return StepName::CONTENT;
    }
}
