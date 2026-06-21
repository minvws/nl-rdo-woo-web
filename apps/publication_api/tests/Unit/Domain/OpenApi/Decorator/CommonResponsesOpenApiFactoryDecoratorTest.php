<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\CommonResponsesOpenApiFactoryDecorator;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiCommonResponsesProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OperationResponseDefinition;
use Shared\Tests\Unit\UnitTestCase;

class CommonResponsesOpenApiFactoryDecoratorTest extends UnitTestCase
{
    public function testContextIsPassedToDecoratedFactory(): void
    {
        $context = ['foo' => 'bar'];
        $captured = new ArrayObject();

        $openApi = $this->createOpenApi();
        $decorated = new readonly class($openApi, $captured) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi, private ArrayObject $captured)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                $captured = $this->captured;
                $captured['context'] = $context;

                return $this->openApi;
            }
        };

        $decorator = new CommonResponsesOpenApiFactoryDecorator($decorated, []);
        $decorator($context);

        $this->assertSame($context, $captured['context']);
    }

    public function testNoProvidersLeavesOperationsWithoutExtraResponses(): void
    {
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator($this->createOpenApi($paths), providers: []);

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertEmpty($responses);
    }

    public function testCommonResponseIsAddedToOperation(): void
    {
        $notFoundResponse = new Response(description: 'Not Found');
        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $notFoundResponse);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertNotNull($responses);
        $this->assertArrayHasKey(404, $responses);
        $this->assertSame($notFoundResponse, $responses[404]);
    }

    public function testExistingResponseIsNotOverwritten(): void
    {
        $existingResponse = new Response(description: 'Existing Not Found');
        $providerResponse = new Response(description: 'Provider Not Found');

        $operation = new Operation()->withResponse(404, $existingResponse);
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($operation));

        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $providerResponse);

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertNotNull($responses);
        $this->assertArrayHasKey(404, $responses);
        $this->assertSame($existingResponse, $responses[404]);
    }

    public function testWhenConditionFalseSkipsResponse(): void
    {
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
            when: static fn (): bool => false,
        );

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertArrayNotHasKey(404, $responses ?? []);
    }

    public function testWhenConditionTrueAddsResponse(): void
    {
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
            when: static fn (): bool => true,
        );

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertNotNull($responses);
        $this->assertArrayHasKey(404, $responses);
    }

    public function testWhenCallbackReceivesCorrectArguments(): void
    {
        $capturedPath = null;
        $capturedMethod = null;
        $capturedOperation = null;

        $operation = new Operation();
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
            when: static function (Operation $op, string $path, string $method) use (&$capturedOperation, &$capturedPath, &$capturedMethod): bool {
                $capturedOperation = $op;
                $capturedPath = $path;
                $capturedMethod = $method;

                return false;
            },
        );

        $paths = new Paths();
        $paths->addPath('/api/test', new PathItem()->withGet($operation));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $decorator([]);

        $this->assertInstanceOf(Operation::class, $capturedOperation);
        $this->assertSame('/api/test', $capturedPath);
        $this->assertSame('GET', $capturedMethod);
    }

    public function testReferenceIsUnwrappedToArrayObjectWithRef(): void
    {
        $reference = new Reference('#/components/responses/NotFound');
        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $reference);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $response = $result->getPaths()->getPath('/test')?->getGet()?->getResponses()[404] ?? null;
        $this->assertInstanceOf(ArrayObject::class, $response);
        $this->assertSame('#/components/responses/NotFound', $response['$ref']);
        $this->assertArrayNotHasKey('summary', $response);
        $this->assertArrayNotHasKey('description', $response);
    }

    public function testReferenceWithSummaryIsUnwrapped(): void
    {
        $reference = new Reference('#/components/responses/NotFound', summary: 'Resource not found');
        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $reference);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $response = $result->getPaths()->getPath('/test')?->getGet()?->getResponses()[404] ?? null;
        $this->assertInstanceOf(ArrayObject::class, $response);
        $this->assertSame('Resource not found', $response['summary']);
    }

    public function testReferenceWithDescriptionIsUnwrapped(): void
    {
        $reference = new Reference('#/components/responses/NotFound', description: 'The resource was not found');
        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $reference);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $response = $result->getPaths()->getPath('/test')?->getGet()?->getResponses()[404] ?? null;
        $this->assertInstanceOf(ArrayObject::class, $response);
        $this->assertSame('The resource was not found', $response['description']);
    }

    public function testReferenceWithExtensionPropertiesIsUnwrapped(): void
    {
        $reference = new Reference('#/components/responses/NotFound')->withExtensionProperty('custom', 'value');
        $this->assertInstanceOf(Reference::class, $reference);
        $responseDefinition = new OperationResponseDefinition(statusCode: 404, response: $reference);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $response = $result->getPaths()->getPath('/test')?->getGet()?->getResponses()[404] ?? null;
        $this->assertInstanceOf(ArrayObject::class, $response);
        $this->assertSame('value', $response['x-custom']);
    }

    public function testCommonResponsesAreAppliedToAllDefinedHttpMethods(): void
    {
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
        );

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()
            ->withGet(new Operation())
            ->withPost(new Operation())
            ->withPut(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $pathItem = $result->getPaths()->getPath('/test');
        $this->assertArrayHasKey(404, $pathItem?->getGet()?->getResponses() ?? []);
        $this->assertArrayHasKey(404, $pathItem?->getPost()?->getResponses() ?? []);
        $this->assertArrayHasKey(404, $pathItem?->getPut()?->getResponses() ?? []);
    }

    public function testUndefinedHttpMethodsAreSkippedGracefully(): void
    {
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
        );

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $this->assertNull($result->getPaths()->getPath('/test')?->getPost());
    }

    public function testCommonResponsesAreAppliedToAllPaths(): void
    {
        $responseDefinition = new OperationResponseDefinition(
            statusCode: 404,
            response: new Response(description: 'Not Found'),
        );

        $paths = new Paths();
        $paths->addPath('/foo', new PathItem()->withGet(new Operation()));
        $paths->addPath('/bar', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$this->createProvider([$responseDefinition])],
        );

        $result = $decorator([]);

        $this->assertArrayHasKey(404, $result->getPaths()->getPath('/foo')?->getGet()?->getResponses() ?? []);
        $this->assertArrayHasKey(404, $result->getPaths()->getPath('/bar')?->getGet()?->getResponses() ?? []);
    }

    public function testResponsesFromMultipleProvidersAreAllApplied(): void
    {
        $firstProvider = $this->createProvider([
            new OperationResponseDefinition(statusCode: 404, response: new Response(description: 'Not Found')),
        ]);
        $secondProvider = $this->createProvider([
            new OperationResponseDefinition(statusCode: 500, response: new Response(description: 'Server Error')),
        ]);

        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator(
            $this->createOpenApi($paths),
            providers: [$firstProvider, $secondProvider],
        );

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertNotNull($responses);
        $this->assertArrayHasKey(404, $responses);
        $this->assertArrayHasKey(500, $responses);
    }

    /**
     * @param array<array-key,OpenApiCommonResponsesProvider> $providers
     */
    private function createDecorator(OpenApi $openApi, array $providers): CommonResponsesOpenApiFactoryDecorator
    {
        $decorated = new readonly class($openApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        return new CommonResponsesOpenApiFactoryDecorator($decorated, $providers);
    }

    /**
     * @param array<array-key,OperationResponseDefinition> $definitions
     */
    private function createProvider(array $definitions): OpenApiCommonResponsesProvider
    {
        return new readonly class($definitions) implements OpenApiCommonResponsesProvider {
            /** @param array<array-key,OperationResponseDefinition> $definitions */
            public function __construct(private array $definitions)
            {
            }

            public function getCommonResponses(): array
            {
                return $this->definitions;
            }
        };
    }

    private function createOpenApi(?Paths $paths = null): OpenApi
    {
        return new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: $paths ?? new Paths(),
            components: new Components(),
        );
    }
}
