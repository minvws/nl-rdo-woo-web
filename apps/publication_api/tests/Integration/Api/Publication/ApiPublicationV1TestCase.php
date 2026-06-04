<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication;

use ApiPlatform\Symfony\Bundle\Test\Client;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Webmozart\Assert\Assert;

use function file_get_contents;
use function sprintf;

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
        $client->getKernelBrowser()->setServerParameter('SSL_CLIENT_VERIFY', 'SUCCESS');
        $client->getKernelBrowser()->setServerParameter('SSL_CLIENT_S_DN', 'CN=' . $sslUserName . ', organizationIdentifier=NTRNL-99999994');

        return $client;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createPublicationApiRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        return self::createPublicationApiClient()->request($method, $url, $options);
    }

    public function getTestFileContent(string $testFileFilename): string
    {
        $kernel = static::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', $kernel->getProjectDir(), $testFileFilename);
        $fileContent = file_get_contents($testFilePath);

        Assert::string($fileContent);

        return $fileContent;
    }
}
