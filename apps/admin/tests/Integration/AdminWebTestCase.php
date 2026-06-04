<?php

declare(strict_types=1);

namespace Admin\Tests\Integration;

use Shared\ApplicationId;
use Shared\Tests\Integration\ContainerTestTrait;
use Shared\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AdminWebTestCase extends WebTestCase
{
    use ContainerTestTrait;
    use IntegrationTestTrait;

    protected static function getApplicationId(): ApplicationId
    {
        return ApplicationId::ADMIN;
    }
}
