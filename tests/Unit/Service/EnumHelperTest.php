<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Service\EnumHelper;
use Shared\Tests\Unit\UnitTestCase;

class EnumHelperTest extends UnitTestCase
{
    public function testGetStringValues(): void
    {
        $result = EnumHelper::getStringValues([
            DossierStatusTransition::DELETE,
            DossierStatusTransition::PUBLISH,
        ]);

        $expectedResult = [
            DossierStatusTransition::DELETE->value,
            DossierStatusTransition::PUBLISH->value,
        ];

        self::assertEquals($expectedResult, $result);
    }
}
