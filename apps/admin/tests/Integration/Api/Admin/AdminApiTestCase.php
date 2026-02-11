<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Shared\ApplicationId;
use Shared\Service\Security\User;
use Shared\Tests\Integration\IntegrationTestTrait;

abstract class AdminApiTestCase extends ApiTestCase
{
    use IntegrationTestTrait;

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

    protected static function getAppId(): ApplicationId
    {
        return ApplicationId::ADMIN;
    }
}
