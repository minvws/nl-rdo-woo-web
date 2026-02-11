<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication;

use ApiPlatform\Symfony\Bundle\Test\Client;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ApiPublicationV1TestCase extends PublicationApiTestCase
{
    public function createPublicationApiClient(string $sslUserName = 'valid.minvws.nl'): Client
    {
        $client = self::createClient(defaultOptions: [
            'headers' => [
                'Accept' => 'application/json, application/ld+json, multipart/form-data',
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
