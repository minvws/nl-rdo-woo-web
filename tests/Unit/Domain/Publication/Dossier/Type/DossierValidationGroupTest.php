<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class DossierValidationGroupTest extends MockeryTestCase
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
