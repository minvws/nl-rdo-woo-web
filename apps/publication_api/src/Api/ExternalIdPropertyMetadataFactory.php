<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class ExternalIdPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $types = $propertyMetadata->getBuiltinTypes() ?? [];
        foreach ($types as $type) {
            if ($type->getClassName() === ExternalId::class) {
                $schema = ['type' => 'string', 'format' => 'external-id'];

                if ($type->isNullable()) {
                    $schema = [
                        'anyOf' => [
                            $schema,
                            ['type' => 'null'],
                        ],
                    ];
                }

                return $propertyMetadata->withSchema($schema);
            }
        }

        return $propertyMetadata;
    }
}
