<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Tests\Unit\UnitTestCase;

final class DossierValidationGroupTest extends UnitTestCase
{
    public function testDossierValidationGroup(): void
    {
        $this->assertMatchesSnapshot(DossierValidationGroup::cases());
    }

    public function testGetValidationGroupsForStepName(): void
    {
        $result = [];
        foreach (StepName::cases() as $stepName) {
            $result[$stepName->value] = DossierValidationGroup::getValidationGroupsForStepName($stepName);
        }

        $this->assertMatchesSnapshot($result);
    }

    public function testGetForWorkflowTransitions(): void
    {
        $result = [];
        foreach (DossierStatusTransition::cases() as $transition) {
            $result[$transition->value] = DossierValidationGroup::getForWorkflowTransitions($transition);
        }

        $this->assertMatchesSnapshot($result);
    }

    public function testAllNonWorkflowGroups(): void
    {
        $result = DossierValidationGroup::allNonWorkflowGroups();

        $this->assertMatchesSnapshot($result);
    }
}
