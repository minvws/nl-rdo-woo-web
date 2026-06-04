<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use PublicationApi\Api\Publication\PublicationV1Api;
use PublicationApi\Domain\OpenApi\Decorator\StripApiPrefixFromPathsOpenApiFactoryDecorator;
use Shared\Tests\Unit\UnitTestCase;

use function array_keys;

class StripApiPrefixFromPathsOpenApiFactoryDecoratorTest extends UnitTestCase
{
    public function testPathsWithPrefixAreStripped(): void
    {
        $paths = new Paths();
        $paths->addPath(PublicationV1Api::API_PREFIX . '/organisation', new PathItem());
        $paths->addPath(PublicationV1Api::API_PREFIX . '/organisation/{organisationId}', new PathItem());

        $openApi = $this->createOpenApiWithPaths($paths);
        $decorator = new StripApiPrefixFromPathsOpenApiFactoryDecorator($this->createDecorated($openApi));

        $result = $decorator([]);

        $resultPaths = array_keys($result->getPaths()->getPaths());
        $this->assertSame(['/organisation', '/organisation/{organisationId}'], $resultPaths);
    }

    public function testPathsWithoutPrefixArePassedThrough(): void
    {
        $paths = new Paths();
        $paths->addPath('/other/path', new PathItem());

        $openApi = $this->createOpenApiWithPaths($paths);
        $decorator = new StripApiPrefixFromPathsOpenApiFactoryDecorator($this->createDecorated($openApi));

        $result = $decorator([]);

        $resultPaths = array_keys($result->getPaths()->getPaths());
        $this->assertSame(['/other/path'], $resultPaths);
    }

    public function testEmptyPathsReturnEmptyPaths(): void
    {
        $openApi = $this->createOpenApiWithPaths(new Paths());
        $decorator = new StripApiPrefixFromPathsOpenApiFactoryDecorator($this->createDecorated($openApi));

        $result = $decorator([]);

        $this->assertSame([], $result->getPaths()->getPaths());
    }

    public function testMixedPathsAreHandledCorrectly(): void
    {
        $paths = new Paths();
        $paths->addPath(PublicationV1Api::API_PREFIX . '/organisation', new PathItem());
        $paths->addPath('/other/path', new PathItem());

        $openApi = $this->createOpenApiWithPaths($paths);
        $decorator = new StripApiPrefixFromPathsOpenApiFactoryDecorator($this->createDecorated($openApi));

        $result = $decorator([]);

        $resultPaths = array_keys($result->getPaths()->getPaths());
        $this->assertSame(['/organisation', '/other/path'], $resultPaths);
    }

    private function createOpenApiWithPaths(Paths $paths): OpenApi
    {
        return new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: $paths,
            components: new Components(),
        );
    }

    private function createDecorated(OpenApi $openApi): OpenApiFactoryInterface
    {
        return new readonly class($openApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };
    }
}
