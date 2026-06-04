<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use BackedEnum;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function enum_exists;
use function is_a;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class TranslatableEnumPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
        private TranslatorInterface $translator,
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

        if (! $unwrapped instanceof EnumType) {
            return $propertyMetadata;
        }

        $className = $unwrapped->getClassName();
        if (! enum_exists($className) || ! is_a($className, TranslatableInterface::class, true)) {
            return $propertyMetadata;
        }

        /** @var class-string<TranslatableInterface&BackedEnum> $className */
        $openapiContext = $propertyMetadata->getOpenapiContext() ?? [];
        $openapiContext['x-enum-varnames'] = array_map(
            fn (TranslatableInterface&BackedEnum $case) => $case->trans($this->translator),
            $className::cases(),
        );

        return $propertyMetadata->withOpenapiContext($openapiContext);
    }
}
