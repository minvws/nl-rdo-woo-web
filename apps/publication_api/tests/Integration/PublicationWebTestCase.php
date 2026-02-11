<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration;

use Shared\ApplicationId;
use Shared\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class PublicationWebTestCase extends WebTestCase
{
    use IntegrationTestTrait;

    protected static function getAppId(): ApplicationId
    {
        return ApplicationId::PUBLICATION_API;
    }
}
