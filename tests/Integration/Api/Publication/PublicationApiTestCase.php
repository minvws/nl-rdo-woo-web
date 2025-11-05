<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Publication;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;

abstract class PublicationApiTestCase extends ApiTestCase
{
    public function createPublicationApiClient(string $sslUserName = 'valid.minvws.nl'): Client
    {
        $client = self::createClient(defaultOptions: [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $client->getKernelBrowser()->setServerParameter('SSL_CLIENT_S_DN_CN', $sslUserName);

        return $client;
    }
}
