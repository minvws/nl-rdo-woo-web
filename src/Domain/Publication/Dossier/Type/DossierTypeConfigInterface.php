<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

interface DossierTypeConfigInterface
{
    public function getDossierType(): DossierType;

    public function getSecurityExpression(): ?Expression;

    public function getStatusWorkflow(): WorkflowInterface;

    public function createInstance(): AbstractDossier;

    /**
     * @return StepDefinitionInterface[]
     */
    public function getSteps(): array;

    public function getCreateRouteName(): string;

    public function getDeleteStrategy(): DossierDeleteStrategyInterface;
}
