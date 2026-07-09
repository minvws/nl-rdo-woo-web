<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Schema;

use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ArrayObject;
use LogicException;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\Schema\CursorPageSchemaFactory;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Webmozart\Assert\Assert;

final class CursorPageSchemaFactoryTest extends UnitTestCase
{
    public function testPatchesCursorPageItemsSchemaForMatchingOperation(): void
    {
        $schemaName = 'Fake.CursorPage';
        $itemSchemaName = 'Fake.Item';

        $schema = $this->createSchemaWithDefinition($schemaName);
        $decorated = $this->createDecoratedFactory($schema);

        $itemOperation = new Get(output: ['class' => stdClass::class]);
        $operation = new GetCollection(output: ['class' => CursorPage::class]);

        $metadataCollection = new ResourceMetadataCollection(
            stdClass::class,
            [new ApiResource(operations: [$itemOperation])],
        );

        $resourceMetadataCollectionFactory = new readonly class($metadataCollection) implements ResourceMetadataCollectionFactoryInterface {
            public function __construct(private ResourceMetadataCollection $metadataCollection)
            {
            }

            public function create(string $resourceClass): ResourceMetadataCollection
            {
                return $this->metadataCollection;
            }
        };

        $definitionNameFactory = new readonly class($schemaName, $itemSchemaName) implements DefinitionNameFactoryInterface {
            public function __construct(
                private string $schemaName,
                private string $itemSchemaName,
            ) {
            }

            public function create(
                string $className,
                string $format = 'json',
                ?string $inputOrOutputClass = null,
                ?Operation $operation = null,
                array $serializerContext = [],
            ): string {
                if ($inputOrOutputClass === CursorPage::class) {
                    return $this->schemaName;
                }

                return $this->itemSchemaName;
            }
        };

        $factory = new CursorPageSchemaFactory($decorated, $resourceMetadataCollectionFactory, $definitionNameFactory);
        $result = $factory->buildSchema(stdClass::class, operation: $operation);

        $definitions = $result->getDefinitions();
        self::assertTrue($definitions->offsetExists($schemaName));

        $definition = $definitions[$schemaName];
        Assert::isInstanceOf($definition, ArrayObject::class);

        $properties = $definition['properties'];
        Assert::isInstanceOf($properties, ArrayObject::class);

        $items = $properties['items'];
        Assert::isInstanceOf($items, ArrayObject::class);

        self::assertSame(
            ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/' . $itemSchemaName]],
            $items->getArrayCopy(),
        );
    }

    public function testDelegatesUnchangedWhenOperationOutputIsNotCursorPage(): void
    {
        $schema = new Schema();
        $spy = new class($schema) implements SchemaFactoryInterface {
            public bool $called = false;

            public function __construct(private readonly Schema $schema)
            {
            }

            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                $this->called = true;

                return $this->schema;
            }
        };

        $factory = new CursorPageSchemaFactory(
            $spy,
            $this->createThrowingMetadataFactory(),
            $this->createThrowingDefinitionNameFactory(),
        );

        $result = $factory->buildSchema(
            stdClass::class,
            operation: new GetCollection(output: ['class' => stdClass::class]),
        );

        self::assertSame($schema, $result);
        self::assertTrue($spy->called, 'Expected the decorated factory to be called.');
    }

    public function testDelegatesUnchangedWhenOperationIsNull(): void
    {
        $schema = new Schema();
        $spy = new class($schema) implements SchemaFactoryInterface {
            public bool $called = false;

            public function __construct(private readonly Schema $schema)
            {
            }

            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                $this->called = true;

                return $this->schema;
            }
        };

        $factory = new CursorPageSchemaFactory(
            $spy,
            $this->createThrowingMetadataFactory(),
            $this->createThrowingDefinitionNameFactory(),
        );

        $result = $factory->buildSchema(stdClass::class);

        self::assertSame($schema, $result);
        self::assertTrue($spy->called, 'Expected the decorated factory to be called.');
    }

    public function testDelegatesUnchangedWhenOperationIsNotGetCollection(): void
    {
        $schema = new Schema();
        $spy = new class($schema) implements SchemaFactoryInterface {
            public bool $called = false;

            public function __construct(private readonly Schema $schema)
            {
            }

            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                $this->called = true;

                return $this->schema;
            }
        };

        $factory = new CursorPageSchemaFactory(
            $spy,
            $this->createThrowingMetadataFactory(),
            $this->createThrowingDefinitionNameFactory(),
        );

        $result = $factory->buildSchema(
            CursorPage::class,
            operation: new Get(output: ['class' => CursorPage::class]),
        );

        self::assertSame($schema, $result);
        self::assertTrue($spy->called, 'Expected the decorated factory to be called.');
    }

    public function testPropagatesSetSchemaFactoryToDecoratedWhenItIsAware(): void
    {
        $decorated = new class implements SchemaFactoryInterface, SchemaFactoryAwareInterface {
            public bool $called = false;

            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                return new Schema();
            }

            public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
            {
                $this->called = true;
            }
        };

        $factory = new CursorPageSchemaFactory(
            $decorated,
            $this->createNoopMetadataFactory(),
            $this->createNoopDefinitionNameFactory(),
        );
        $factory->setSchemaFactory($decorated);

        self::assertTrue($decorated->called);
    }

    public function testSetSchemaFactoryIsNoopWhenDecoratedIsNotAware(): void
    {
        $decorated = new readonly class implements SchemaFactoryInterface {
            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                return new Schema();
            }
        };

        $this->expectNotToPerformAssertions();

        $factory = new CursorPageSchemaFactory(
            $decorated,
            $this->createNoopMetadataFactory(),
            $this->createNoopDefinitionNameFactory(),
        );
        $factory->setSchemaFactory($decorated);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createDecoratedFactory(Schema $schema): SchemaFactoryInterface
    {
        return new readonly class($schema) implements SchemaFactoryInterface {
            public function __construct(private Schema $schema)
            {
            }

            public function buildSchema(
                string $className,
                string $format = 'json',
                string $type = Schema::TYPE_OUTPUT,
                ?Operation $operation = null,
                ?Schema $schema = null,
                ?array $serializerContext = null,
                bool $forceCollection = false,
            ): Schema {
                return $this->schema;
            }
        };
    }

    private function createSchemaWithDefinition(string $schemaName): Schema
    {
        $schema = new Schema();
        $definitions = new ArrayObject([
            $schemaName => new ArrayObject([
                'type' => 'object',
                'properties' => new ArrayObject([
                    'items' => new ArrayObject(['type' => 'array', 'items' => new ArrayObject()]),
                ]),
            ]),
        ]);

        $schema->setDefinitions($definitions);

        return $schema;
    }

    /**
     * Returns a metadata factory stub that throws if called.
     * Use in tests where the decorator is expected to short-circuit before reaching this dependency.
     */
    private function createThrowingMetadataFactory(): ResourceMetadataCollectionFactoryInterface
    {
        return new readonly class implements ResourceMetadataCollectionFactoryInterface {
            public function create(string $resourceClass): ResourceMetadataCollection
            {
                throw new LogicException('Resource metadata factory should not be called.');
            }
        };
    }

    /**
     * Returns a definition name factory stub that throws if called.
     * Use in tests where the decorator is expected to short-circuit before reaching this dependency.
     */
    private function createThrowingDefinitionNameFactory(): DefinitionNameFactoryInterface
    {
        return new readonly class implements DefinitionNameFactoryInterface {
            public function create(
                string $className,
                string $format = 'json',
                ?string $inputOrOutputClass = null,
                ?Operation $operation = null,
                array $serializerContext = [],
            ): string {
                throw new LogicException('Definition name factory should not be called.');
            }
        };
    }

    /**
     * Returns a metadata factory stub that returns an empty collection.
     * Use in tests that exercise setSchemaFactory wiring, not the schema-building path.
     */
    private function createNoopMetadataFactory(): ResourceMetadataCollectionFactoryInterface
    {
        return new readonly class implements ResourceMetadataCollectionFactoryInterface {
            public function create(string $resourceClass): ResourceMetadataCollection
            {
                return new ResourceMetadataCollection($resourceClass);
            }
        };
    }

    /**
     * Returns a definition name factory stub that returns a placeholder name.
     * Use in tests that exercise setSchemaFactory wiring, not the schema-building path.
     */
    private function createNoopDefinitionNameFactory(): DefinitionNameFactoryInterface
    {
        return new readonly class implements DefinitionNameFactoryInterface {
            public function create(
                string $className,
                string $format = 'json',
                ?string $inputOrOutputClass = null,
                ?Operation $operation = null,
                array $serializerContext = [],
            ): string {
                return 'unused';
            }
        };
    }
}
