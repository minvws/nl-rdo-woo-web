<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use PublicationApi\Api\MainDocument\MainDocumentDtoInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function array_values;
use function is_subclass_of;

/**
 * Restricts the OpenAPI schema `enum` values for the `type` property on per-dossier
 * MainDocumentRequestDto and MainDocumentResponseDto classes to only the AttachmentTypes
 * allowed by that dossier, as declared via the DTO's own getAllowedTypes() method.
 *
 * Runs at priority -10 (outermost user-defined decorator, since higher priority = innermost in Symfony)
 * so it can filter the `x-enum-varnames` that TranslatableEnumPropertyMetadataFactory adds for all cases.
 */
#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory', priority: -10)]
final readonly class MainDocumentAttachmentTypePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if ($property !== 'type') {
            return $propertyMetadata;
        }

        if (! is_subclass_of($resourceClass, MainDocumentDtoInterface::class)) {
            return $propertyMetadata;
        }

        $nativeType = $propertyMetadata->getNativeType();
        if ($nativeType === null) {
            return $propertyMetadata;
        }

        $nullable = $nativeType instanceof NullableType;
        $unwrapped = $nullable ? $nativeType->getWrappedType() : $nativeType;

        if (! $unwrapped instanceof EnumType || $unwrapped->getClassName() !== AttachmentType::class) {
            return $propertyMetadata;
        }

        $allowedTypes = array_values($resourceClass::getAllowedTypes());
        if ($allowedTypes === []) {
            return $propertyMetadata;
        }

        $allowedValues = array_values(array_map(
            static fn (AttachmentType $case): string => $case->value,
            $allowedTypes,
        ));

        $schema = ['type' => 'string', 'enum' => $allowedValues];
        if ($nullable) {
            $schema = ['anyOf' => [$schema, ['type' => 'null']]];
        }

        $propertyMetadata = $propertyMetadata->withSchema($schema);

        // Also filter x-enum-varnames to only include labels for the allowed types,
        // ensuring the positional mapping between enum values and display names is correct.
        $openapiContext = $propertyMetadata->getOpenapiContext() ?? [];
        $openapiContext['x-enum-varnames'] = array_values(array_map(
            fn (AttachmentType $case): string => $case->trans($this->translator),
            $allowedTypes,
        ));

        return $propertyMetadata->withOpenapiContext($openapiContext);
    }
}
