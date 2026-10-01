<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use BackedEnum;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_exists;
use function array_map;
use function enum_exists;
use function is_a;
use function is_array;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory', priority: -5)]
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

        if ($unwrapped instanceof EnumType) {
            $translations = $this->getTranslations($unwrapped->getClassName());
            if ($translations === null) {
                return $propertyMetadata;
            }

            $openapiContext = $propertyMetadata->getOpenapiContext() ?? [];
            $openapiContext['x-enum-varnames'] = $translations;

            return $propertyMetadata->withOpenapiContext($openapiContext);
        }

        if (! $unwrapped instanceof CollectionType) {
            return $propertyMetadata;
        }

        $valueType = $unwrapped->getCollectionValueType();
        if (! $valueType instanceof EnumType) {
            return $propertyMetadata;
        }

        $translations = $this->getTranslations($valueType->getClassName());
        $schema = $propertyMetadata->getSchema();
        if ($translations === null || $schema === null) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withSchema($this->addTranslationsToItems($schema, $translations));
    }

    /**
     * @return list<string>|null
     */
    private function getTranslations(string $className): ?array
    {
        if (! enum_exists($className) || ! is_a($className, TranslatableInterface::class, true)) {
            return null;
        }

        /** @var class-string<TranslatableInterface&BackedEnum> $className */
        return array_map(
            fn (TranslatableInterface&BackedEnum $case) => $case->trans($this->translator),
            $className::cases(),
        );
    }

    /**
     * @param array<array-key, mixed> $schema
     * @param list<string> $translations
     *
     * @return array<array-key, mixed>
     */
    private function addTranslationsToItems(array $schema, array $translations): array
    {
        if (array_key_exists('items', $schema) && is_array($schema['items'])) {
            $schema['items']['x-enum-varnames'] = $translations;
        }

        if (array_key_exists('anyOf', $schema) && is_array($schema['anyOf'])) {
            $schema['anyOf'] = array_map(
                fn (mixed $subSchema): mixed => is_array($subSchema)
                    ? $this->addTranslationsToItems($subSchema, $translations)
                    : $subSchema,
                $schema['anyOf'],
            );
        }

        return $schema;
    }
}
