<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use BackedEnum;
use Shared\Service\EnumHelper;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class BackedEnumCollectionPropertyMetadataFactory implements PropertyMetadataFactoryInterface
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

        $isNullable = $nativeType instanceof NullableType;
        $wrappedType = $isNullable ? $nativeType->getWrappedType() : $nativeType;

        if (! $wrappedType instanceof CollectionType) {
            return $propertyMetadata;
        }

        $valueType = $wrappedType->getCollectionValueType();
        if (! $valueType instanceof BackedEnumType) {
            return $propertyMetadata;
        }

        if (! $valueType->getBackingType()->isIdentifiedBy(TypeIdentifier::STRING)) {
            return $propertyMetadata;
        }

        $className = $valueType->getClassName();
        Assert::isAOf($className, BackedEnum::class);

        $schema = [
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'enum' => EnumHelper::getStringValues($className::cases()),
            ],
        ];

        if ($isNullable) {
            $schema = ['anyOf' => [$schema, ['type' => 'null']]];
        }

        return $propertyMetadata->withSchema($schema);
    }
}
