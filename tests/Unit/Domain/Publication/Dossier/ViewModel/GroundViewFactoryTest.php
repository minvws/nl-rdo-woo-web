<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use App\Tests\Unit\UnitTestCase;

final class GroundViewFactoryTest extends UnitTestCase
{
    public function testMakeAsArray(): void
    {
        $result = (new GroundViewFactory())->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }
}
