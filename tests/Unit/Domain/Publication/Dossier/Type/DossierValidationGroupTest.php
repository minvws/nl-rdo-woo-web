<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\Step\StepDefinition;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Tests\Unit\UnitTestCase;

final class DossierValidationGroupTest extends UnitTestCase
{
    public function testGetValidationGroupForStepIncludesTypeSpecificGroup(): void
    {
        $this->assertEquals(
            [
                DossierValidationGroup::DETAILS->value,
                DossierValidationGroup::ANNUAL_REPORT_DETAILS->value,
            ],
            DossierValidationGroup::getValidationGroupsForStep(
                new StepDefinition(StepName::DETAILS, DossierType::ANNUAL_REPORT)
            )
        );
    }
}
