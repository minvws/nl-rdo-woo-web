<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use Shared\ApplicationId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SharedWebTestCase extends WebTestCase
{
    use IntegrationTestTrait;

    public static function getAppId(): ApplicationId
    {
        return ApplicationId::SHARED;
    }
}
