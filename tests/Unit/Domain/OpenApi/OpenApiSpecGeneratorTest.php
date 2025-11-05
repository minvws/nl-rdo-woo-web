<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Domain\OpenApi\OpenApiSpecGenerator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class OpenApiSpecGeneratorTest extends UnitTestCase
{
    public function testGetSpec(): void
    {
        $tag = 'foo';
        $openApi = \Mockery::mock(OpenApi::class);

        $openApiFactory = \Mockery::mock(OpenApiFactoryInterface::class);
        $openApiFactory->expects('__invoke')
            ->with(['filter_tags' => $tag])
            ->once()
            ->andReturn($openApi);

        $serializer = \Mockery::mock(SerializerInterface::class);
        $serializer->expects('serialize')
            ->with($openApi, 'json')
            ->once()
            ->andReturn(\json_encode([]));

        $openApiSpecGenerator = new OpenApiSpecGenerator($openApiFactory, $serializer);
        $openApiSpecGenerator->getSpec($tag);

        // assert instance caching on consecutive calls
        $openApiSpecGenerator->getSpec($tag);
    }

    public function testGetSpecWithDifferentTags(): void
    {
        $tag1 = 'foo';
        $tag2 = 'bar';
        $openApi = \Mockery::mock(OpenApi::class);

        $openApiFactory = \Mockery::mock(OpenApiFactoryInterface::class);
        $openApiFactory->expects('__invoke')
            ->with(['filter_tags' => $tag1])
            ->once()
            ->andReturn($openApi);
        $openApiFactory->expects('__invoke')
            ->with(['filter_tags' => $tag2])
            ->once()
            ->andReturn($openApi);

        $serializer = \Mockery::mock(SerializerInterface::class);
        $serializer->expects('serialize')
            ->with($openApi, 'json')
            ->times(2)
            ->andReturn(\json_encode(['foo']), \json_encode(['bar']));

        $openApiSpecGenerator = new OpenApiSpecGenerator($openApiFactory, $serializer);

        self::assertNotEquals($openApiSpecGenerator->getSpec($tag1), $openApiSpecGenerator->getSpec($tag2));
    }
}
