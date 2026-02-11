<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiSchemaComponentProvider;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsDecorator(decorates: 'api_platform.openapi.factory', priority: 50)]
final readonly class SchemaComponentOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    /**
     * @param iterable<array-key,OpenApiSchemaComponentProvider> $schemaProviders
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[AutowireIterator('publication_api.open_api.schema_component_provider')]
        private iterable $schemaProviders,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $newSchemas = $this->mergeComponents($schemas);

        return $newSchemas->count() === 0
            ? $openApi
            : $openApi->withComponents($components->withSchemas($newSchemas));
    }

    /**
     * @param ArrayObject<string,array<string,mixed>|bool> $schemas
     *
     * @return ArrayObject<string,array<string,mixed>|bool>
     */
    private function mergeComponents(ArrayObject $schemas): ArrayObject
    {
        foreach ($this->schemaProviders as $provider) {
            $providedSchemas = $provider->getSchemas();

            foreach ($providedSchemas as $key => $schema) {
                $schemas[$key] = $schema;
            }
        }

        return $schemas;
    }
}
