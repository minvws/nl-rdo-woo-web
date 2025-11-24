<?php

declare(strict_types=1);

namespace Public\Tests\Integration;

use Shared\ApplicationId;
use Shared\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class PublicWebTestCase extends WebTestCase
{
    use IntegrationTestTrait;

    public static function getAppId(): ApplicationId
    {
        return ApplicationId::PUBLIC;
    }
}
