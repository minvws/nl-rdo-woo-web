<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

#[AutoconfigureTag('woo_platform.publication.dossier_type_config')]
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
