<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Mockery;
use PublicationApi\Domain\OpenApi\OpenApiSpecGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\SerializerInterface;

use function json_encode;

class OpenApiSpecGeneratorTest extends UnitTestCase
{
    public function testGetSpec(): void
    {
        $openApi = Mockery::mock(OpenApi::class);

        $openApiFactory = Mockery::mock(OpenApiFactoryInterface::class);
        $openApiFactory->expects('__invoke')
            ->once()
            ->andReturn($openApi);

        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer->expects('serialize')
            ->with($openApi, 'json')
            ->once()
            ->andReturn(json_encode([]));

        $openApiSpecGenerator = new OpenApiSpecGenerator($openApiFactory, $serializer);
        $openApiSpecGenerator->getSpec();

        // assert instance caching on consecutive calls
        $openApiSpecGenerator->getSpec();
    }
}
