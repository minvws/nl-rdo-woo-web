<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use Shared\Tests\Unit\UnitTestCase;

final class GroundViewFactoryTest extends UnitTestCase
{
    public function testMakeAsArray(): void
    {
        $result = (new GroundViewFactory())->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }
}
