<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

use function end;
use function explode;

#[AsDecorator(decorates: 'api_platform.json_schema.definition_name_factory')]
final readonly class WooDefinitionNameFactoryDecorator implements DefinitionNameFactoryInterface
{
    public function __construct(private DefinitionNameFactoryInterface $decorated)
    {
    }

    public function create(
        string $className,
        string $format = 'json',
        ?string $inputOrOutputClass = null,
        ?Operation $operation = null,
        array $serializerContext = [],
    ): string {
        $schemaType = $serializerContext['schema_type'] ?? null;

        if ($schemaType === 'input' && $inputOrOutputClass !== null) {
            $parts = explode('\\', $inputOrOutputClass);

            return end($parts);
        }

        return $this->decorated->create($className, $format, $inputOrOutputClass, $operation, $serializerContext);
    }
}
