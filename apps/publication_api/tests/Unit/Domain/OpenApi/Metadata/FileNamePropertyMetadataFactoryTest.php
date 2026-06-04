<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\FileNamePropertyMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\FileName;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class FileNamePropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForFilenameProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(FileName::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'fileName',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new FileNamePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'fileName');

        $expectedSchema = [
            'type' => 'string',
            'format' => 'filename',
        ];
        self::assertSame($expectedSchema, $result->getSchema());
    }

    public function testItDoesNotModifyNonFilenameProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new FileNamePropertyMetadataFactory($decorated);
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
