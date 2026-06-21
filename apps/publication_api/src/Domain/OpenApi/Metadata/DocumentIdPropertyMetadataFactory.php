<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Shared\ValueObject\DocumentId;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

use function trim;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class DocumentIdPropertyMetadataFactory implements PropertyMetadataFactoryInterface
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

        if (! $unwrapped instanceof ObjectType || $unwrapped->getClassName() !== DocumentId::class) {
            return $propertyMetadata;
        }

        $schema = [
            'type' => 'string',
            'format' => 'document-id',
            'minLength' => DocumentId::MIN_LENGTH,
            'maxLength' => DocumentId::MAX_LENGTH,
            'pattern' => trim(DocumentId::PATTERN, '/'),
        ];

        if ($nullable) {
            $schema = ['anyOf' => [$schema, ['type' => 'null']]];
        }

        return $propertyMetadata->withSchema($schema);
    }
}
