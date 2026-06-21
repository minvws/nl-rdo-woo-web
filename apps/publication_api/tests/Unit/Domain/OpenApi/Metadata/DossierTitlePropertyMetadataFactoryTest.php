<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\DossierTitlePropertyMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use stdClass;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class DossierTitlePropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForDossierTitleProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(DossierTitle::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DossierTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertSame(['type' => 'string'], $result->getSchema());
    }

    public function testItSetsNullableSchemaForNullableDossierTitleProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new ObjectType(DossierTitle::class)),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DossierTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertSame(
            ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
            $result->getSchema(),
        );
    }

    public function testItDoesNotModifyNonDossierTitleProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'other',
            propertyMetadata: new ApiProperty()->withNativeType(new ObjectType(stdClass::class)),
        );

        $factory = new DossierTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'other');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertiesWithNullNativeType(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new DossierTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertNull($result->getSchema());
    }

    private function createMockedPropertyMetadataFactory(
        string $property,
        ApiProperty $propertyMetadata,
    ): PropertyMetadataFactoryInterface&MockInterface {
        $mock = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $mock->expects('create')
            ->with('TestClass', $property, [])
            ->andReturn($propertyMetadata);

        return $mock;
    }
}
