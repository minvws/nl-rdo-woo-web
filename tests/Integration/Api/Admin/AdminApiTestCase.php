<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Service\Security\User;

abstract class AdminApiTestCase extends ApiTestCase
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
