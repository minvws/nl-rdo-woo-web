<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use App\Api\OpenApi\GroupedOpenApiFactory;
use App\Api\OpenApi\UsageDetector\OpenApiComponentsUsageDetector;
use App\Api\OpenApi\UsageDetector\UsedComponents;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class GroupedOpenApiFactoryTest extends UnitTestCase
{
    private OpenApiFactoryInterface&MockInterface $decorated;
    private OpenApiComponentsUsageDetector&MockInterface $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decorated = \Mockery::mock(OpenApiFactoryInterface::class);
        $this->detector = \Mockery::mock(OpenApiComponentsUsageDetector::class);
    }

    public function testInvokeWithoutSettingFilterTagAndNoComponentsIsANoOp(): void
    {
        $context = [];

        $openApi = new OpenApi(
            info: new Info(
                title: 'Test API',
                version: '1.0.0',
            ),
            servers: [],
            paths: new Paths(),
        );

        $this->decorated
            ->shouldReceive('__invoke')
            ->with($context)
            ->once()
            ->andReturn($openApi);

        $this->detector
            ->shouldReceive('detect')
            ->with($openApi)
            ->once()
            ->andReturn(UsedComponents::new());

        $factory = new GroupedOpenApiFactory(
            decorated: $this->decorated,
            openApiComponentsUsageDetector: $this->detector,
        );

        $result = $factory($context);

        self::assertSame($openApi, $result);
    }

    public function testInvoke(): void
    {
        $context = ['filter_tag' => 'publication-v1'];

        $paths = new Paths();

        $includedPathItem = new PathItem();
        $includedPathItem = $includedPathItem->withGet(new Operation(
            operationId: 'getTest',
            tags: ['should-be-included', 'publication-v1'],
            responses: [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/IncludedResponse',
                            ],
                        ],
                    ],
                ],
            ],
        ));
        $paths->addPath('/included', $includedPathItem);

        $excludedPathItem = new PathItem();
        $excludedPathItem = $excludedPathItem->withGet(new Operation(
            operationId: 'getExcluded',
            tags: ['should-be-excluded', 'admin'],
            responses: [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ExcludedResponse',
                            ],
                        ],
                    ],
                ],
            ],
        ));
        $paths->addPath('/excluded', $excludedPathItem);

        $includedResponseSchema = new Schema();
        $includedResponseSchema->setDefinitions([
            'type' => 'object',
            'properties' => [
                'data' => ['type' => 'string'],
            ],
        ]);

        $excludedResponseSchema = new Schema();
        $excludedResponseSchema->setDefinitions([
            'type' => 'object',
            'properties' => [
                'error' => ['type' => 'string'],
            ],
        ]);

        $foobarParameter = new Parameter(
            name: 'FoobarParameter',
            in: 'query',
            required: false,
            schema: ['type' => 'string'],
        );

        $components = new Components(
            schemas: new \ArrayObject([
                'IncludedResponse' => $includedResponseSchema,
                'ExcludedResponse' => $excludedResponseSchema,
            ]),
            parameters: new \ArrayObject([
                'FoobarParameter' => $foobarParameter,
            ]),
        );

        $usedComponents = UsedComponents::new()
            ->mark('schemas', 'IncludedResponse');

        $openApi = new OpenApi(
            info: new Info(
                title: 'Test API',
                version: '1.0.0',
            ),
            servers: [],
            paths: $paths,
            components: $components,
        );

        $this->decorated
            ->shouldReceive('__invoke')
            ->with($context)
            ->once()
            ->andReturn($openApi);

        $this->detector
            ->shouldReceive('detect')
            ->with(\Mockery::type(OpenApi::class))
            ->once()
            ->andReturn($usedComponents);

        $factory = new GroupedOpenApiFactory(
            decorated: $this->decorated,
            openApiComponentsUsageDetector: $this->detector,
        );

        $result = $factory($context);

        $actualPaths = array_keys($result->getPaths()->getPaths());
        $actualSchemas = array_keys($result->getComponents()->getSchemas()?->getArrayCopy() ?? []);

        self::assertNotSame($openApi, $result);
        self::assertSame(['/included'], $actualPaths);
        self::assertSame(['IncludedResponse'], $actualSchemas);
    }
}
