<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Steps\ContentStepDefinition;
use App\Domain\Publication\Dossier\Type\Covenant\Steps\DetailsStepDefinition;
use App\Domain\Publication\Dossier\Type\Covenant\Steps\PublicationStepDefinition;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Workflow\WorkflowInterface;

readonly class CovenantConfig implements DossierTypeConfigInterface
{
    public function __construct(
        private WorkflowInterface $covenantWorkflow,
        private DetailsStepDefinition $detailsStepDefinition,
        private ContentStepDefinition $contentStepDefinition,
        private PublicationStepDefinition $publicationStepDefinition,
        private CovenantDeleteStrategy $deleteStrategy,
    ) {
    }

    public function getDossierType(): DossierType
    {
        return DossierType::COVENANT;
    }

    public function getSecurityExpression(): ?Expression
    {
        return new Expression('is_granted("ROLE_SUPER_ADMIN")');
    }

    public function getStatusWorkflow(): WorkflowInterface
    {
        return $this->covenantWorkflow;
    }

    public function createInstance(): AbstractDossier
    {
        return new Covenant();
    }

    public function getSteps(): array
    {
        return [
            $this->detailsStepDefinition,
            $this->contentStepDefinition,
            $this->publicationStepDefinition,
        ];
    }

    public function getCreateRouteName(): string
    {
        return 'app_admin_dossier_covenant_details_create';
    }

    public function getDeleteStrategy(): DossierDeleteStrategyInterface
    {
        return $this->deleteStrategy;
    }
}
