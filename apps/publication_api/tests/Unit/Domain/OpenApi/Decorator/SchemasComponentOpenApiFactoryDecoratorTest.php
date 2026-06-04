<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\SchemasComponentOpenApiFactoryDecorator;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiSchemasComponentProvider;
use Shared\Tests\Unit\UnitTestCase;

class SchemasComponentOpenApiFactoryDecoratorTest extends UnitTestCase
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

        $decorator = new SchemasComponentOpenApiFactoryDecorator($decorated, []);
        $decorator($context);

        $this->assertSame($context, $captured['context']);
    }

    public function testNoProvidersAndNoExistingSchemasReturnsOriginalOpenApi(): void
    {
        $openApi = $this->createOpenApi();
        $decorator = $this->createDecorator($openApi, providers: []);

        $result = $decorator([]);

        $this->assertSame($openApi, $result);
    }

    public function testProviderSchemasAreAddedToComponents(): void
    {
        $mySchema = ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]];
        $anotherSchema = ['type' => 'string'];

        $provider = $this->createProvider(['MySchema' => $mySchema, 'AnotherSchema' => $anotherSchema]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$provider]);

        $result = $decorator([]);

        $schemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $schemas);
        $this->assertSame($mySchema, $schemas['MySchema']);
        $this->assertSame($anotherSchema, $schemas['AnotherSchema']);
    }

    public function testExistingSchemaIsNotOverwritten(): void
    {
        $existingSchema = new Schema();
        $providerSchema = ['type' => 'object', 'description' => 'from provider'];

        $existingSchemas = new ArrayObject(['MySchema' => $existingSchema]);
        $openApi = $this->createOpenApi(schemas: $existingSchemas);

        $provider = $this->createProvider(['MySchema' => $providerSchema]);

        $decorator = $this->createDecorator($openApi, providers: [$provider]);

        $result = $decorator([]);

        $schemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $schemas);
        $this->assertSame($existingSchema, $schemas['MySchema']);
    }

    public function testSchemasFromMultipleProvidersAreMerged(): void
    {
        $firstSchema = ['type' => 'object'];
        $secondSchema = ['type' => 'string'];

        $firstProvider = $this->createProvider(['FirstSchema' => $firstSchema]);
        $secondProvider = $this->createProvider(['SecondSchema' => $secondSchema]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$firstProvider, $secondProvider]);

        $result = $decorator([]);

        $schemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $schemas);
        $this->assertSame($firstSchema, $schemas['FirstSchema']);
        $this->assertSame($secondSchema, $schemas['SecondSchema']);
    }

    public function testFirstProviderWinsOnConflictingKey(): void
    {
        $firstSchema = ['type' => 'object', 'description' => 'first'];
        $secondSchema = ['type' => 'object', 'description' => 'second'];

        $firstProvider = $this->createProvider(['MySchema' => $firstSchema]);
        $secondProvider = $this->createProvider(['MySchema' => $secondSchema]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$firstProvider, $secondProvider]);

        $result = $decorator([]);

        $schemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $schemas);
        $this->assertSame($firstSchema, $schemas['MySchema']);
    }

    public function testNullComponentSchemasAreHandledGracefully(): void
    {
        $schema = ['type' => 'object'];
        $provider = $this->createProvider(['MySchema' => $schema]);

        $openApi = $this->createOpenApi(schemas: null);
        $decorator = $this->createDecorator($openApi, providers: [$provider]);

        $result = $decorator([]);

        $schemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $schemas);
        $this->assertSame($schema, $schemas['MySchema']);
    }

    /**
     * @param array<array-key,OpenApiSchemasComponentProvider> $providers
     */
    private function createDecorator(OpenApi $openApi, array $providers): SchemasComponentOpenApiFactoryDecorator
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

        return new SchemasComponentOpenApiFactoryDecorator($decorated, $providers);
    }

    /**
     * @param array<string,array<string,mixed>|bool> $schemas
     */
    private function createProvider(array $schemas): OpenApiSchemasComponentProvider
    {
        return new readonly class($schemas) implements OpenApiSchemasComponentProvider {
            /** @param array<string,array<string,mixed>|bool> $schemas */
            public function __construct(private array $schemas)
            {
            }

            public function getSchemas(): array
            {
                return $this->schemas;
            }
        };
    }

    /**
     * @param ArrayObject<string, Schema>|null $schemas
     */
    private function createOpenApi(?ArrayObject $schemas = null): OpenApi
    {
        return new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: new Paths(),
            components: new Components(schemas: $schemas),
        );
    }
}
