<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @codeCoverageIgnore
 */
readonly class InvestigationReportConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $investigationReportWorkflow,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::INVESTIGATION_REPORT;
    }

    public function getSecurityExpression(): ?Expression
    {
        return new Expression('is_granted("ROLE_SUPER_ADMIN")');
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->investigationReportWorkflow;
    }

    public function createInstance(): AbstractDossier
    {
        return new InvestigationReport();
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
        return 'app_admin_dossier_investigationreport_details_create';
    }
}
