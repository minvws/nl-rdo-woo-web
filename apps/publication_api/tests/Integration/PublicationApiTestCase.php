<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Shared\ApplicationId;
use Shared\Tests\Integration\ContainerTestTrait;
use Shared\Tests\Integration\DatabaseTestTrait;
use Shared\Tests\Integration\IntegrationTestTrait;

abstract class PublicationApiTestCase extends ApiTestCase
{
    use ContainerTestTrait;
    use DatabaseTestTrait;
    use IntegrationTestTrait;

    protected static function getApplicationId(): ApplicationId
    {
        return ApplicationId::PUBLICATION_API;
    }
}
