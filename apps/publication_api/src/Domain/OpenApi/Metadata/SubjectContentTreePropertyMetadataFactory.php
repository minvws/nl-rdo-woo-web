<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory')]
final readonly class SubjectContentTreePropertyMetadataFactory implements PropertyMetadataFactoryInterface
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

        if (! $unwrapped instanceof ObjectType || $unwrapped->getClassName() !== SubjectContentTree::class) {
            return $propertyMetadata;
        }

        $schema = [
            'type' => 'object',
            'required' => ['title', 'intro', 'children', 'outro'],
            'properties' => [
                'title' => ['type' => 'string'],
                'intro' => ['type' => 'string'],
                'children' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['title', 'body', 'children'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'body' => ['type' => 'string'],
                            'children' => ['type' => 'array'],
                        ],
                    ],
                ],
                'outro' => ['type' => 'string'],
            ],
        ];

        if ($nullable) {
            $schema = ['anyOf' => [$schema, ['type' => 'null']]];
        }

        return $propertyMetadata->withSchema($schema);
    }
}
