<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Mockery;
use PublicationApi\Domain\OpenApi\Decorator\ChoiceValuesSchemaDecorator;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use ReflectionClass;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Webmozart\Assert\Assert;

use function substr_count;

final class ChoiceValuesSchemaDecoratorTest extends PublicationApiTestCase
{
    public function testItReturnsOpenApiUnchangedWhenNoSchemas(): void
    {
        $openApi = $this->buildOpenApi(null);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($openApi);

        $this->assertSame($openApi, new ChoiceValuesSchemaDecorator($decorated)());
    }

    public function testItAddsAllowedValuesDescriptionToMatchingProperty(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['foo', 'bar', 'baz'])])]
            public array $tags = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'tags' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $property = $properties['tags'];
        Assert::isArray($property);

        $description = $property['description'];
        Assert::string($description);

        $this->assertSame('array', $property['type']);
        $this->assertStringContainsString('Allowed values:', $description);
        $this->assertStringContainsString('`foo`', $description);
        $this->assertStringContainsString('`bar`', $description);
        $this->assertStringContainsString('`baz`', $description);
        $this->assertSame(['type' => 'string'], $property['items']);
    }

    public function testItIgnoresPropertiesWithoutAllConstraint(): void
    {
        $schemas = new ArrayObject([
            'SomeDto' => new ArrayObject([
                'properties' => [
                    'name' => new ArrayObject(['type' => 'string']),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas['SomeDto'];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $name = $properties['name'];
        Assert::isInstanceOf($name, ArrayObject::class);

        $this->assertArrayNotHasKey('description', $name->getArrayCopy());
    }

    public function testItIgnoresNonArrayObjectSchemas(): void
    {
        $schemas = new ArrayObject(['SomeDto' => ['properties' => ['name' => ['type' => 'string']]]]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();
    }

    public function testDescriptionContainsBulletPoints(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['a', 'b'])])]
            public array $items = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'items' => new ArrayObject(['type' => 'array', 'items' => ['type' => 'string']]),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $property = $properties['items'];
        Assert::isArray($property);

        $description = $property['description'];
        Assert::string($description);

        $this->assertStringContainsString('- `a`', $description);
        $this->assertStringContainsString('- `b`', $description);
    }

    public function testItIgnoresSchemaWithArrayObjectProperties(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['foo', 'bar'])])]
            public array $tags = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => new ArrayObject([
                    'tags' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                ]),
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isInstanceOf($properties, ArrayObject::class);

        $tag = $properties['tags'];
        Assert::isInstanceOf($tag, ArrayObject::class);

        $this->assertArrayNotHasKey('description', $tag->getArrayCopy());
    }

    public function testItIgnoresPropertyNotPresentInSchema(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['foo', 'bar'])])]
            public array $tags = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject(['properties' => []]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $this->assertEmpty($schema['properties']);
    }

    public function testItAppliesDescriptionToMultipleProperties(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['foo', 'bar'])])]
            public array $tags = [];

            /** @var array<int, string> */
            #[All([new Choice(['a', 'b'])])]
            public array $categories = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'tags' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                    'categories' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $tags = $properties['tags'];
        Assert::isArray($tags);
        $tagsDescription = $tags['description'];
        Assert::string($tagsDescription);
        $this->assertStringContainsString('`foo`', $tagsDescription);
        $this->assertStringContainsString('`bar`', $tagsDescription);

        $categories = $properties['categories'];
        Assert::isArray($categories);
        $categoriesDescription = $categories['description'];
        Assert::string($categoriesDescription);
        $this->assertStringContainsString('`a`', $categoriesDescription);
        $this->assertStringContainsString('`b`', $categoriesDescription);
    }

    public function testItIgnoresChoiceConstraintWithoutAllWrapper(): void
    {
        $dto = new class {
            #[Choice(['foo', 'bar'])]
            public string $tag = '';
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'tag' => new ArrayObject(['type' => 'string']),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $tag = $properties['tag'];
        Assert::isInstanceOf($tag, ArrayObject::class);

        $this->assertArrayNotHasKey('description', $tag->getArrayCopy());
    }

    public function testItDeduplicatesChoiceValues(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new Choice(['foo', 'foo', 'bar'])])]
            public array $tags = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'tags' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $property = $properties['tags'];
        Assert::isArray($property);

        $description = $property['description'];
        Assert::string($description);

        $this->assertSame(1, substr_count($description, '`foo`'));
    }

    public function testItIgnoresAllConstraintWithoutChoiceNested(): void
    {
        $dto = new class {
            /** @var array<int, string> */
            #[All([new NotBlank()])]
            public array $tags = [];
        };

        $schemaName = new ReflectionClass($dto)->getShortName();

        $schemas = new ArrayObject([
            $schemaName => new ArrayObject([
                'properties' => [
                    'tags' => new ArrayObject([
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ]),
                ],
            ]),
        ]);

        $decorated = Mockery::mock(OpenApiFactoryInterface::class);
        $decorated->expects('__invoke')
            ->once()
            ->andReturn($this->buildOpenApi($schemas));

        new ChoiceValuesSchemaDecorator($decorated)();

        $schema = $schemas[$schemaName];
        Assert::isInstanceOf($schema, ArrayObject::class);

        $properties = $schema['properties'];
        Assert::isArray($properties);

        $tag = $properties['tags'];
        Assert::isInstanceOf($tag, ArrayObject::class);

        $this->assertArrayNotHasKey('description', $tag->getArrayCopy());
    }

    private function buildOpenApi(?ArrayObject $schemas): OpenApi
    {
        return new OpenApi(new Info('Test', '1.0'), [], new Paths(), new Components($schemas));
    }
}
