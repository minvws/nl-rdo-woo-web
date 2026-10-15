<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use Shared\ApplicationId;
use Shared\Service\Security\User;
use Shared\Tests\Integration\ContainerTestTrait;
use Shared\Tests\Integration\DatabaseTestTrait;
use Shared\Tests\Integration\IntegrationTestTrait;

abstract class AdminApiTestCase extends ApiTestCase
{
    use ContainerTestTrait;
    use DatabaseTestTrait;
    use IntegrationTestTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function createAdminApiClient(User $user): Client
    {
        $defaultOptions = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        return self::createClient(defaultOptions: $defaultOptions)
            ->loginUser($user, 'balie');
    }

    protected static function getApplicationId(): ApplicationId
    {
        return ApplicationId::ADMIN;
    }
}
