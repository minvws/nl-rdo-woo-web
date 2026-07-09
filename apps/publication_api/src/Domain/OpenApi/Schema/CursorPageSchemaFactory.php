<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema;

use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ArrayObject;
use PublicationApi\Api\Pagination\CursorPage;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

use function is_array;
use function is_string;

#[AsDecorator(decorates: 'api_platform.json_schema.schema_factory')]
final readonly class CursorPageSchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    public function __construct(
        private SchemaFactoryInterface $decorated,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        #[Autowire(service: 'api_platform.json_schema.definition_name_factory')]
        private DefinitionNameFactoryInterface $definitionNameFactory,
    ) {
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
        $result = $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);

        if (! $operation instanceof GetCollection) {
            return $result;
        }

        $collectionOutput = $operation->getOutput();
        if (! is_array($collectionOutput) || ($collectionOutput['class'] ?? null) !== CursorPage::class) {
            return $result;
        }

        // $className is the resource class (e.g. AdviceResource); CursorPage is the output DTO

        try {
            $metadataCollection = $this->resourceMetadataCollectionFactory->create($className);
            $itemOperation = $metadataCollection->getOperation(null, false, true);
        } catch (OperationNotFoundException) {
            return $result;
        }

        if (! $itemOperation instanceof Get) {
            return $result;
        }

        $itemOutput = $itemOperation->getOutput();
        if (! is_array($itemOutput)) {
            return $result;
        }

        $itemClass = $itemOutput['class'] ?? null;
        if (! is_string($itemClass)) {
            return $result;
        }

        // Narrow both string values to class-string for DefinitionNameFactoryInterface::create()
        Assert::classExists($className);
        Assert::classExists($itemClass);

        $cursorPageSchemaName = $this->definitionNameFactory->create(
            $className,
            $format,
            CursorPage::class,
            $operation,
        );
        $itemSchemaName = $this->definitionNameFactory->create($className, $format, $itemClass, $itemOperation);

        $definitions = $result->getDefinitions();
        if (! $definitions->offsetExists($cursorPageSchemaName)) {
            return $result;
        }

        /** @var ArrayObject<string, mixed> $definition */
        $definition = $definitions[$cursorPageSchemaName];

        /** @var ArrayObject<string, mixed> $properties */
        $properties = $definition['properties'] ?? new ArrayObject();
        $properties['items'] = new ArrayObject([
            'type' => 'array',
            'items' => ['$ref' => '#/components/schemas/' . $itemSchemaName],
        ]);
        $definition['properties'] = $properties;

        return $result;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->decorated instanceof SchemaFactoryAwareInterface) {
            $this->decorated->setSchemaFactory($schemaFactory);
        }
    }
}
