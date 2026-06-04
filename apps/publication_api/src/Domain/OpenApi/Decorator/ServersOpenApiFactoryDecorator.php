<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use PublicationApi\Api\Publication\PublicationV1Api;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

use function ltrim;
use function rtrim;
use function sprintf;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class ServersOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    /**
     * @param array<array-key,mixed> $servers
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[Autowire(param: 'publication_api_servers')]
        private array $servers,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        if ($this->servers === []) {
            return $openApi;
        }

        $serverObjects = [];

        Assert::allIsArray($this->servers);

        foreach ($this->servers as $server) {
            Assert::keyExists($server, 'url');
            $url = $server['url'];
            Assert::stringNotEmpty($url);

            Assert::keyExists($server, 'description');
            $description = $server['description'];
            Assert::string($description);

            $newUrl = sprintf('%s/%s', rtrim($url, '/'), ltrim(PublicationV1Api::API_PREFIX, '/'));

            $serverObjects[] = new Server($newUrl, $description);
        }

        return $openApi->withServers($serverObjects);
    }
}
