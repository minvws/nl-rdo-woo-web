<?php

declare(strict_types=1);

namespace App\Tests\Unit\ViewModel\Factory;

use App\Tests\Unit\UnitTestCase;
use App\ViewModel\Factory\GroundViewFactory;

final class GroundViewFactoryTest extends UnitTestCase
{
    public function testMakeAsArray(): void
    {
        $result = (new GroundViewFactory())->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }
}
