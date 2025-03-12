<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

interface DossierTypeConfigInterface
{
    public function getDossierType(): DossierType;

    public function getSecurityExpression(): ?Expression;

    public function getStatusWorkflow(): WorkflowInterface;

    /**
     * @return class-string<AbstractDossier>
     */
    public function getEntityClass(): string;

    /**
     * @return array<array-key, class-string>
     */
    public function getSubEntityClasses(): array;

    /**
     * @return StepDefinitionInterface[]
     */
    public function getSteps(): array;

    public function getCreateRouteName(): string;

    public function getAttachmentStepName(): ?StepName;
}
