<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\AddRequestBodyToUploadOperationsOpenApiFactoryDecorator;
use Shared\Tests\Unit\UnitTestCase;

class AddRequestBodyToUploadOperationsOpenApiFactoryDecoratorTest extends UnitTestCase
{
    public function testEmptyPathsProducesNoErrors(): void
    {
        $decorator = $this->createDecorator($this->createOpenApi());

        $result = $decorator([]);

        $this->assertEmpty($result->getPaths()->getPaths());
    }

    public function testContextIsPassedThroughToDecoratedFactory(): void
    {
        $context = ['some_key' => 'some_value'];

        $openApi = $this->createOpenApi();
        $spy = new class($openApi) implements OpenApiFactoryInterface {
            /** @var array<array-key,mixed> */
            public ?array $capturedContext = null;

            public function __construct(private readonly OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                $this->capturedContext = $context;

                return $this->openApi;
            }
        };

        $decorator = new AddRequestBodyToUploadOperationsOpenApiFactoryDecorator($spy);
        $decorator($context);

        $this->assertSame($context, $spy->capturedContext);
    }

    public function testPathWithNoPutOperationIsLeftUntouched(): void
    {
        $getOperation = new Operation(operationId: 'get_something');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($getOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $pathItem = $result->getPaths()->getPath('/test');
        $this->assertNotNull($pathItem);
        $this->assertNull($pathItem->getPut());
    }

    public function testPutOperationWithoutOperationIdIsLeftUntouched(): void
    {
        $putOperation = new Operation();
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $pathItem = $result->getPaths()->getPath('/test');
        $this->assertNotNull($pathItem);
        $this->assertNull($pathItem->getPut()?->getRequestBody());
    }

    public function testNonUploadPutOperationIsLeftUntouched(): void
    {
        $putOperation = new Operation(operationId: 'update_something');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $pathItem = $result->getPaths()->getPath('/test');
        $this->assertNotNull($pathItem);
        $this->assertNull($pathItem->getPut()?->getRequestBody());
    }

    public function testAttachmentUploadOperationGetsRequestBody(): void
    {
        $putOperation = new Operation(operationId: 'some_resource_attachment_upload');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $requestBody = $result->getPaths()->getPath('/test')?->getPut()?->getRequestBody();
        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertOctetStreamRequestBody($requestBody);
    }

    public function testMainDocumentUploadOperationGetsRequestBody(): void
    {
        $putOperation = new Operation(operationId: 'some_resource_main_document_upload');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $requestBody = $result->getPaths()->getPath('/test')?->getPut()?->getRequestBody();
        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertOctetStreamRequestBody($requestBody);
    }

    public function testWooDecisionDocumentUploadOperationGetsRequestBody(): void
    {
        $putOperation = new Operation(operationId: 'woo_decision_document_upload');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $requestBody = $result->getPaths()->getPath('/test')?->getPut()?->getRequestBody();
        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertOctetStreamRequestBody($requestBody);
    }

    public function testOperationIdContainingButNotEndingWithAttachmentUploadIsNotMatched(): void
    {
        $putOperation = new Operation(operationId: 'attachment_upload_metadata');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $this->assertNull($result->getPaths()->getPath('/test')?->getPut()?->getRequestBody());
    }

    public function testOperationIdContainingButNotEndingWithMainDocumentUploadIsNotMatched(): void
    {
        $putOperation = new Operation(operationId: 'main_document_upload_meta');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $this->assertNull($result->getPaths()->getPath('/test')?->getPut()?->getRequestBody());
    }

    public function testOtherOperationsOnSamePathItemArePreserved(): void
    {
        $getOperation = new Operation(operationId: 'get_resource');
        $putOperation = new Operation(operationId: 'resource_attachment_upload');
        $paths = new Paths();
        $paths->addPath('/test', new PathItem()->withGet($getOperation)->withPut($putOperation));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $pathItem = $result->getPaths()->getPath('/test');
        $this->assertNotNull($pathItem);
        $this->assertSame('get_resource', $pathItem->getGet()?->getOperationId());
        $this->assertInstanceOf(RequestBody::class, $pathItem->getPut()?->getRequestBody());
    }

    public function testMultiplePathsAreAllProcessed(): void
    {
        $paths = new Paths();
        $paths->addPath('/upload', new PathItem()->withPut(new Operation(operationId: 'doc_attachment_upload')));
        $paths->addPath('/regular', new PathItem()->withPut(new Operation(operationId: 'update_resource')));
        $paths->addPath('/another-upload', new PathItem()->withPut(new Operation(operationId: 'doc_main_document_upload')));

        $decorator = $this->createDecorator($this->createOpenApi($paths));

        $result = $decorator([]);

        $this->assertInstanceOf(RequestBody::class, $result->getPaths()->getPath('/upload')?->getPut()?->getRequestBody());
        $this->assertNull($result->getPaths()->getPath('/regular')?->getPut()?->getRequestBody());
        $this->assertInstanceOf(RequestBody::class, $result->getPaths()->getPath('/another-upload')?->getPut()?->getRequestBody());
    }

    private function assertOctetStreamRequestBody(RequestBody $requestBody): void
    {
        $this->assertSame('The file to upload in raw binary format', $requestBody->getDescription());
        $this->assertTrue($requestBody->getRequired());

        $content = $requestBody->getContent();
        $this->assertInstanceOf(ArrayObject::class, $content);
        $this->assertArrayHasKey('application/octet-stream', $content);

        $mediaType = $content['application/octet-stream'];
        $this->assertInstanceOf(MediaType::class, $mediaType);

        $schema = $mediaType->getSchema();
        $this->assertInstanceOf(ArrayObject::class, $schema);
        $this->assertSame('string', $schema['type']);
        $this->assertSame('binary', $schema['format']);
    }

    private function createDecorator(OpenApi $openApi): AddRequestBodyToUploadOperationsOpenApiFactoryDecorator
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

        return new AddRequestBodyToUploadOperationsOpenApiFactoryDecorator($decorated);
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
