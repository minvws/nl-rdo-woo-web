<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\ApiVersionHeaderOpenApiFactoryDecorator;
use PublicationApi\EventSubscriber\ApiVersionHeaderSubscriber;
use Shared\Tests\Unit\UnitTestCase;

class ApiVersionHeaderOpenApiFactoryDecoratorTest extends UnitTestCase
{
    private const string API_VERSION = '1.2.3';

    public function testHeaderComponentIsAddedToComponents(): void
    {
        $decorator = $this->createDecorator($this->createOpenApi());

        $result = $decorator([]);

        $headers = $result->getComponents()->getHeaders();
        $this->assertNotNull($headers);
        $this->assertArrayHasKey('ApiVersion', $headers);

        $header = $headers['ApiVersion'];
        $this->assertInstanceOf(ArrayObject::class, $header);

        $schema = $header['schema'];
        $this->assertInstanceOf(ArrayObject::class, $schema);

        $this->assertSame(self::API_VERSION, $schema['example']);
    }

    public function testApiVersionHeaderIsAddedToExistingResponse(): void
    {
        $response = new Response(description: 'OK');
        $operation = new Operation()->withResponse('200', $response);
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($operation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        /** @var Response $resultResponse */
        $resultResponse = $result->getPaths()->getPath('/test')?->getGet()?->getResponses()['200'] ?? null;
        $this->assertInstanceOf(Response::class, $resultResponse);

        $headers = $resultResponse->getHeaders();
        $this->assertNotNull($headers);
        $this->assertArrayHasKey(ApiVersionHeaderSubscriber::HEADER_NAME, $headers);

        $apiVersion = $headers[ApiVersionHeaderSubscriber::HEADER_NAME];
        $this->assertInstanceOf(ArrayObject::class, $apiVersion);

        $this->assertSame('#/components/headers/ApiVersion', $apiVersion['$ref'] ?? null);
    }

    public function testApiVersionHeaderIsAddedToAllStatusCodesOfAllOperations(): void
    {
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()
            ->withGet(new Operation()->withResponse('200', new Response(description: 'OK')))
            ->withPut(
                new Operation()
                    ->withResponse('200', new Response(description: 'OK'))
                    ->withResponse('422', new Response(description: 'Validation failed')),
            ));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $getResponses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses() ?? [];
        $getResponse200 = $getResponses['200'] ?? null;
        $this->assertInstanceOf(Response::class, $getResponse200);

        $getResponse200Headers = $getResponse200->getHeaders();
        $this->assertNotNull($getResponse200Headers);

        $this->assertArrayHasKey(ApiVersionHeaderSubscriber::HEADER_NAME, $getResponse200Headers);

        $putResponses = $result->getPaths()->getPath('/test')?->getPut()?->getResponses() ?? [];
        $putResponse200 = $putResponses['200'] ?? null;
        $this->assertInstanceOf(Response::class, $putResponse200);
        $putResponse200Headers = $putResponse200->getHeaders();
        $this->assertNotNull($putResponse200Headers);

        $putResponse422 = $putResponses['422'] ?? null;
        $this->assertInstanceOf(Response::class, $putResponse422);
        $putResponse422Headers = $putResponse422->getHeaders();
        $this->assertNotNull($putResponse422Headers);

        $this->assertArrayHasKey(ApiVersionHeaderSubscriber::HEADER_NAME, $putResponse200Headers);
        $this->assertArrayHasKey(ApiVersionHeaderSubscriber::HEADER_NAME, $putResponse422Headers);
    }

    public function testOperationWithNoResponsesIsLeftUntouched(): void
    {
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet(new Operation()));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $this->assertEmpty($responses);
    }

    public function testExistingResponseHeadersArePreserved(): void
    {
        $existingHeaders = new ArrayObject(['X-Custom' => new ArrayObject(['description' => 'Custom header'])]);
        $response = new Response(description: 'OK', headers: $existingHeaders);
        $operation = new Operation()->withResponse('200', $response);
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($operation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $responses = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $response200 = $responses['200'] ?? null;
        $this->assertInstanceOf(Response::class, $response200);
        $resultHeaders = $response200->getHeaders();

        $this->assertInstanceOf(ArrayObject::class, $resultHeaders);
        $this->assertArrayHasKey('X-Custom', $resultHeaders);
        $this->assertArrayHasKey(ApiVersionHeaderSubscriber::HEADER_NAME, $resultHeaders);
    }

    public function testExistingApiVersionHeaderIsNotOverwritten(): void
    {
        $existingRef = new ArrayObject(['$ref' => '#/components/headers/CustomVersion']);
        $existingHeaders = new ArrayObject([ApiVersionHeaderSubscriber::HEADER_NAME => $existingRef]);
        $response = new Response(description: 'OK', headers: $existingHeaders);
        $operation = new Operation()->withResponse('200', $response);
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($operation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $resultHeader = $result->getPaths()->getPath('/test')?->getGet()?->getResponses();
        $response200 = $resultHeader['200'] ?? null;

        $this->assertInstanceOf(Response::class, $response200);
        $resultHeaders = $response200->getHeaders();
        $this->assertNotNull($resultHeaders);

        $resultHeader = $resultHeaders[ApiVersionHeaderSubscriber::HEADER_NAME] ?? null;
        $this->assertInstanceOf(ArrayObject::class, $resultHeader);
        $this->assertArrayHasKey('$ref', $resultHeader);
        $this->assertSame('#/components/headers/CustomVersion', $resultHeader['$ref'] ?? null);
    }

    public function testEmptyPathsProducesNoErrors(): void
    {
        $decorator = $this->createDecorator($this->createOpenApi());

        $result = $decorator([]);

        $this->assertEmpty($result->getPaths()->getPaths());
    }

    private function createDecorator(OpenApi $openApi): ApiVersionHeaderOpenApiFactoryDecorator
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

        return new ApiVersionHeaderOpenApiFactoryDecorator($decorated, self::API_VERSION);
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
