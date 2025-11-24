<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Shared\ApplicationId;

abstract class SharedApiTestCase extends ApiTestCase
{
    use IntegrationTestTrait;

    public static function getAppId(): ApplicationId
    {
        return ApplicationId::SHARED;
    }
}
