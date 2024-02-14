<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;

class UnitTestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;
}
