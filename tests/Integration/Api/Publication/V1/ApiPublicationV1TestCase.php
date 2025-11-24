<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Publication\V1;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Shared\Tests\Integration\SharedApiTestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ApiPublicationV1TestCase extends SharedApiTestCase
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

    /**
     * @param array<string, mixed> $options
     */
    public function createPublicationApiRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        return self::createPublicationApiClient()->request($method, $url, $options);
    }
}
