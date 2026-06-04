<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use PublicationApi\Api\Publication\PublicationV1Api;
use PublicationApi\Domain\OpenApi\Decorator\ServersOpenApiFactoryDecorator;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function ltrim;
use function rtrim;
use function sprintf;

class ServersOpenApiFactoryDecoratorTest extends UnitTestCase
{
    public function testEmptyServersReturnsOriginalOpenApi(): void
    {
        $originalOpenApi = $this->createOpenApi();

        $decorated = new readonly class($originalOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $decorator = new ServersOpenApiFactoryDecorator($decorated, []);
        $result = $decorator([]);

        $this->assertSame($originalOpenApi, $result);
        $this->assertSame([], $result->getServers());
    }

    public function testSingleServer(): void
    {
        $originalOpenApi = $this->createOpenApi();
        $url = $this->getFaker()->url();
        $desc = $this->getFaker()->text();

        $decorated = new readonly class($originalOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $decorator = new ServersOpenApiFactoryDecorator($decorated, [
            ['url' => $url, 'description' => $desc],
        ]);
        $result = $decorator([]);

        $servers = $result->getServers();
        $this->assertCount(1, $servers);
        Assert::isInstanceOf($servers[0], Server::class);

        $this->assertEquals($this->getExpectedUrl($url), $servers[0]->getUrl());
        $this->assertEquals($desc, $servers[0]->getDescription());
    }

    public function testMultipleServers(): void
    {
        $url1 = $this->getFaker()->url();
        $desc1 = $this->getFaker()->text();
        $url2 = $this->getFaker()->url();
        $desc2 = $this->getFaker()->text();

        $originalOpenApi = $this->createOpenApi();

        $decorated = new readonly class($originalOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $decorator = new ServersOpenApiFactoryDecorator($decorated, [
            ['url' => $url1, 'description' => $desc1],
            ['url' => $url2, 'description' => $desc2],
        ]);
        $result = $decorator([]);

        $servers = $result->getServers();
        $this->assertCount(2, $servers);
        Assert::isInstanceOf($servers[0], Server::class);
        Assert::isInstanceOf($servers[1], Server::class);
        $this->assertEquals($this->getExpectedUrl($url1), $servers[0]->getUrl());
        $this->assertEquals($desc1, $servers[0]->getDescription());
        $this->assertEquals($this->getExpectedUrl($url2), $servers[1]->getUrl());
        $this->assertEquals($desc2, $servers[1]->getDescription());
    }

    private function createOpenApi(): OpenApi
    {
        return new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: new Paths(),
            components: new Components(),
        );
    }

    private function getExpectedUrl(string $url): string
    {
        return sprintf('%s/%s', rtrim($url, '/'), ltrim(PublicationV1Api::API_PREFIX, '/'));
    }
}
