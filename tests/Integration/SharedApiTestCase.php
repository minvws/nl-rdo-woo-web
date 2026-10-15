<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use ApiPlatform\Test\ApiTestCase;
use Shared\ApplicationId;

abstract class SharedApiTestCase extends ApiTestCase
{
    use IntegrationTestTrait;

    protected static ?bool $alwaysBootKernel = true;

    protected static function getApplicationId(): ApplicationId
    {
        return ApplicationId::SHARED;
    }
}
