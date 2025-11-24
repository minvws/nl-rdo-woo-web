<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Shared\Service\Security\User;
use Shared\Tests\Integration\SharedApiTestCase;

abstract class AdminApiTestCase extends SharedApiTestCase
{
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
}
