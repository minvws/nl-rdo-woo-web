<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Steps;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepName;

readonly class PublicationStepDefinition implements StepDefinitionInterface
{
    public function getName(): StepName
    {
        return StepName::PUBLICATION;
    }

    public function isCompleted(AbstractDossier $dossier): bool
    {
        return $dossier->getStatus()->isPublished()
            || $dossier->hasFuturePublicationDate();
    }

    public function getConceptEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_publish_concept';
    }

    public function getEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_publish_edit';
    }
}
