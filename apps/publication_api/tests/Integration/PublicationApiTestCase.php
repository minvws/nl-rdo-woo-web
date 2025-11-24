<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Shared\ApplicationId;
use Shared\Tests\Integration\IntegrationTestTrait;

abstract class PublicationApiTestCase extends ApiTestCase
{
    use IntegrationTestTrait;

    public static function getAppId(): ApplicationId
    {
        return ApplicationId::PUBLICATION_API;
    }
}
