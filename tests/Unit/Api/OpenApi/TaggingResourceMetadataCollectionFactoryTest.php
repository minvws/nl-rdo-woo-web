<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Api\OpenApi\TaggingResourceMetadataCollectionFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class TaggingResourceMetadataCollectionFactoryTest extends UnitTestCase
{
    private ResourceMetadataCollectionFactoryInterface&MockInterface $decorated;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decorated = \Mockery::mock(ResourceMetadataCollectionFactoryInterface::class);
    }

    public function testCreateOnEmptyCollection(): void
    {
        $resourceClass = 'App\\Api\\SomeResource';
        $resourceMetadataCollection = new ResourceMetadataCollection(
            resourceClass: $resourceClass,
        );

        $this->decorated
            ->expects('create')
            ->with($resourceClass)
            ->andReturn($resourceMetadataCollection);

        $factory = new TaggingResourceMetadataCollectionFactory($this->decorated);

        // @phpstan-ignore argument.type
        $result = $factory->create($resourceClass);

        $this->assertSame($result, $resourceMetadataCollection);
    }

    public function testCreateOnApiResourceWithoutOperations(): void
    {
        $resources = [new ApiResource()];

        $resourceClass = 'App\\Api\\SomeResource';
        $resourceMetadataCollection = new ResourceMetadataCollection(
            resourceClass: $resourceClass,
            input: $resources,
        );

        $this->decorated
            ->expects('create')
            ->with($resourceClass)
            ->andReturn($resourceMetadataCollection);

        $factory = new TaggingResourceMetadataCollectionFactory($this->decorated);

        // @phpstan-ignore argument.type
        $result = $factory->create($resourceClass);

        $this->assertSame($result, $resourceMetadataCollection);
    }

    public function testCreateForResourcesThatAreNotConfiguredToReceiveTags(): void
    {
        $resources = [
            new ApiResource(
                operations: [
                    HttpOperation::METHOD_GET => new HttpOperation(
                        method: HttpOperation::METHOD_GET,
                        uriTemplate: '/some-resource',
                        openapi: new OpenApiOperation(
                            tags: $getTags = ['tag1', 'tag2'],
                        ),
                    ),
                    HttpOperation::METHOD_POST => new HttpOperation(
                        method: HttpOperation::METHOD_POST,
                        uriTemplate: '/some-resource',
                        openapi: new OpenApiOperation(
                            tags: $postTags = ['tag3', 'tag4'],
                        ),
                    ),
                ],
            ),
        ];

        $resourceClass = 'App\\Api\\SomeResource';
        $resourceMetadataCollection = new ResourceMetadataCollection(
            resourceClass: $resourceClass,
            input: $resources,
        );

        $this->decorated
            ->expects('create')
            ->with($resourceClass)
            ->andReturn($resourceMetadataCollection);

        $factory = new TaggingResourceMetadataCollectionFactory($this->decorated);

        // @phpstan-ignore argument.type
        $result = $factory->create($resourceClass);

        $expectedGetTags = $this->getTags($result[0], HttpOperation::METHOD_GET);
        $expectedPostTags = $this->getTags($result[0], HttpOperation::METHOD_POST);

        $this->assertSame($expectedGetTags, $getTags);
        $this->assertSame($expectedPostTags, $postTags);
    }

    public function testCreate(): void
    {
        $resources = [
            new ApiResource(
                operations: [
                    HttpOperation::METHOD_GET => new HttpOperation(
                        method: HttpOperation::METHOD_GET,
                        uriTemplate: '/some-resource-1',
                        openapi: new OpenApiOperation(
                            tags: $getTagsResourceOne = ['tag1', 'tag2'],
                        ),
                    ),
                    HttpOperation::METHOD_POST => new HttpOperation(
                        method: HttpOperation::METHOD_POST,
                        uriTemplate: '/some-resource-1',
                        openapi: new OpenApiOperation(
                            tags: $postTagsResouceOne = ['tag3', 'tag4'],
                        ),
                    ),
                ],
            ),
            new ApiResource(
                operations: [
                    HttpOperation::METHOD_GET => new HttpOperation(
                        method: HttpOperation::METHOD_GET,
                        uriTemplate: '/some-resource-2',
                        openapi: new OpenApiOperation(
                            tags: $getTagsResourceTwo = ['tag5', 'tag6'],
                        ),
                    ),
                ],
            ),
        ];

        $resourceClass = 'App\\Api\\Publication\\V1\\SomeResource';
        $resourceMetadataCollection = new ResourceMetadataCollection(
            resourceClass: $resourceClass,
            input: $resources,
        );

        $this->decorated
            ->expects('create')
            ->with($resourceClass)
            ->andReturn($resourceMetadataCollection);

        $factory = new TaggingResourceMetadataCollectionFactory($this->decorated);

        // @phpstan-ignore argument.type
        $result = $factory->create($resourceClass);

        $expectedGetTagsResourcOne = $this->getTags($result[0], HttpOperation::METHOD_GET);
        $expectedPostTagsResourceOne = $this->getTags($result[0], HttpOperation::METHOD_POST);
        $expectedGetTagsResourceTwo = $this->getTags($result[1], HttpOperation::METHOD_GET);

        $this->assertSame($expectedGetTagsResourcOne, [...$getTagsResourceOne, 'publication-v1']);
        $this->assertSame($expectedPostTagsResourceOne, [...$postTagsResouceOne, 'publication-v1']);
        $this->assertSame($expectedGetTagsResourceTwo, [...$getTagsResourceTwo, 'publication-v1']);
    }

    public function testCreateWithOperationOpenapiContainingBoolOrWebhook(): void
    {
        $resources = [
            new ApiResource(
                operations: [
                    HttpOperation::METHOD_GET => new HttpOperation(
                        method: HttpOperation::METHOD_GET,
                        uriTemplate: '/some-resource-1',
                        openapi: new OpenApiOperation(),
                    ),
                    HttpOperation::METHOD_POST => new HttpOperation(
                        method: HttpOperation::METHOD_GET,
                        uriTemplate: '/some-resource-1',
                        openapi: false,
                    ),
                    'some-webhook' => new HttpOperation(
                        method: HttpOperation::METHOD_POST,
                        uriTemplate: '/some-resource-1',
                        openapi: new Webhook('some-webhook'),
                    ),
                ],
            ),
        ];

        $resourceClass = 'App\\Api\\Publication\\V1\\SomeResource';
        $resourceMetadataCollection = new ResourceMetadataCollection(
            resourceClass: $resourceClass,
            input: $resources,
        );

        $this->decorated
            ->expects('create')
            ->with($resourceClass)
            ->andReturn($resourceMetadataCollection);

        $factory = new TaggingResourceMetadataCollectionFactory($this->decorated);

        // @phpstan-ignore argument.type
        $result = $factory->create($resourceClass);

        $operations = $this->getOperations($result[0]);

        $expectedGetTags = $this->getTags($result[0], HttpOperation::METHOD_GET);

        $this->assertSame($expectedGetTags, ['publication-v1']);
        $this->assertFalse($operations[HttpOperation::METHOD_POST]->getOpenapi());
        $this->assertInstanceOf(Webhook::class, $operations['some-webhook']->getOpenapi());
    }

    /**
     * @return array<array-key,string>
     */
    private function getTags(?ApiResource $resource, string $operationName): array
    {
        if ($resource === null) {
            return [];
        }

        $operation = $this->getOperations($resource)[$operationName] ?? null;

        $openapi = $operation?->getOpenapi();
        if (! $openapi instanceof OpenApiOperation) {
            return [];
        }

        /** @var array<array-key,string> */
        return $openapi->getTags() ?? [];
    }

    /**
     * @return array<string,HttpOperation>
     */
    private function getOperations(?ApiResource $resource): array
    {
        if ($resource === null) {
            return [];
        }

        /** @var array<string,HttpOperation> */
        return [...$resource->getOperations()?->getIterator() ?? []];
    }
}
