<?php

declare(strict_types=1);

namespace Admin\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Shared\ValueObject\PlainDate;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class PlainDatePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $nativeType = $propertyMetadata->getNativeType();
        if ($nativeType === null) {
            return $propertyMetadata;
        }

        $nullable = $nativeType instanceof NullableType;
        $unwrapped = $nullable ? $nativeType->getWrappedType() : $nativeType;

        if (! $unwrapped instanceof ObjectType || $unwrapped->getClassName() !== PlainDate::class) {
            return $propertyMetadata;
        }

        $schema = [
            'type' => 'string',
            'format' => 'date',
        ];

        if ($nullable) {
            $schema = ['anyOf' => [$schema, ['type' => 'null']]];
        }

        return $propertyMetadata->withSchema($schema);
    }
}
