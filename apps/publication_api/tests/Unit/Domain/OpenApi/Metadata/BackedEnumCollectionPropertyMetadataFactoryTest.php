<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use PublicationApi\Domain\OpenApi\Metadata\BackedEnumCollectionPropertyMetadataFactory;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;

final class BackedEnumCollectionPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testSetsEnumSchema(): void
    {
        $resourceClass = $this->getFaker()->word();
        $property = $this->getFaker()->word();
        $randomEnumClass = AttachmentWithdrawReason::class;

        $propertyMetadataFactory = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->expects('create')
            ->with($resourceClass, $property, [])
            ->andReturn(new ApiProperty()->withNativeType(Type::list(Type::enum($randomEnumClass))));

        $backedEnumCollectionPropertyMetadataFactory = new BackedEnumCollectionPropertyMetadataFactory($propertyMetadataFactory);
        $apiProperty = $backedEnumCollectionPropertyMetadataFactory->create($resourceClass, $property);

        $expectedSchema = [
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'enum' => ['unrelated', 'incomplete'],
            ],
        ];
        self::assertSame($expectedSchema, $apiProperty->getSchema());
    }

    public function testSetsNullableEnumSchema(): void
    {
        $resourceClass = $this->getFaker()->word();
        $property = $this->getFaker()->word();
        $randomEnumClass = AttachmentWithdrawReason::class;

        $propertyMetadataFactory = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->expects('create')
            ->with($resourceClass, $property, [])
            ->andReturn(new ApiProperty()->withNativeType(new NullableType(Type::list(Type::enum($randomEnumClass)))));

        $backedEnumCollectionPropertyMetadataFactory = new BackedEnumCollectionPropertyMetadataFactory($propertyMetadataFactory);
        $apiProperty = $backedEnumCollectionPropertyMetadataFactory->create($resourceClass, $property);

        $expectedSchema = [
            'anyOf' => [
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['unrelated', 'incomplete'],
                    ],
                ],
                ['type' => 'null'],
            ],
        ];
        self::assertSame($expectedSchema, $apiProperty->getSchema());
    }

    public function testIgnoresCollectionOfNonEnum(): void
    {
        $resourceClass = $this->getFaker()->word();
        $property = $this->getFaker()->word();

        $propertyMetadataFactory = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->expects('create')
            ->with($resourceClass, $property, [])
            ->andReturn(new ApiProperty()->withNativeType(Type::list(Type::string())));

        $backedEnumCollectionPropertyMetadataFactory = new BackedEnumCollectionPropertyMetadataFactory($propertyMetadataFactory);
        $apiProperty = $backedEnumCollectionPropertyMetadataFactory->create($resourceClass, $property);

        self::assertNull($apiProperty->getSchema());
    }

    public function testIgnoresPropertyWithoutNativeType(): void
    {
        $resourceClass = $this->getFaker()->word();
        $property = $this->getFaker()->word();

        $propertyMetadataFactory = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->expects('create')
            ->with($resourceClass, $property, [])
            ->andReturn(new ApiProperty());

        $backedEnumCollectionPropertyMetadataFactory = new BackedEnumCollectionPropertyMetadataFactory($propertyMetadataFactory);
        $apiProperty = $backedEnumCollectionPropertyMetadataFactory->create($resourceClass, $property);

        self::assertNull($apiProperty->getSchema());
    }
}
