<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\DocumentNumberPropertyMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use stdClass;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class DocumentNumberPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForDocumentNumberProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(DocumentNumber::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'documentNumber',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DocumentNumberPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'documentNumber');

        self::assertSame(['type' => 'string'], $result->getSchema());
    }

    public function testItSetsNullableSchemaForNullableDocumentNumberProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new ObjectType(DocumentNumber::class)),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'documentNumber',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DocumentNumberPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'documentNumber');

        $expectedSchema = [
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'null'],
            ],
        ];
        self::assertSame($expectedSchema, $result->getSchema());
    }

    public function testItDoesNotModifyNonDocumentNumberProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty()->withNativeType(new ObjectType(stdClass::class)),
        );

        $factory = new DocumentNumberPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertiesWithNullNativeType(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new DocumentNumberPropertyMetadataFactory($decorated);
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
