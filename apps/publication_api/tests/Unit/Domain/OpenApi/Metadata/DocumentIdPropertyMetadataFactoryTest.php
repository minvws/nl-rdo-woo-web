<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\DocumentIdPropertyMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use stdClass;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

use function trim;

final class DocumentIdPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForDocumentIdProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(DocumentId::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'documentId',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DocumentIdPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'documentId');

        $expectedSchema = [
            'type' => 'string',
            'format' => 'document-id',
            'minLength' => DocumentId::MIN_LENGTH,
            'maxLength' => DocumentId::MAX_LENGTH,
            'pattern' => trim(DocumentId::PATTERN, '/'),
        ];
        self::assertSame($expectedSchema, $result->getSchema());
    }

    public function testItSetsNullableSchemaForNullableDocumentIdProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new ObjectType(DocumentId::class)),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'documentId',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new DocumentIdPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'documentId');

        $expectedSchema = [
            'anyOf' => [
                [
                    'type' => 'string',
                    'format' => 'document-id',
                    'minLength' => DocumentId::MIN_LENGTH,
                    'maxLength' => DocumentId::MAX_LENGTH,
                    'pattern' => trim(DocumentId::PATTERN, '/'),
                ],
                ['type' => 'null'],
            ],
        ];
        self::assertSame($expectedSchema, $result->getSchema());
    }

    public function testItDoesNotModifyNonDocumentIdProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty()->withNativeType(new ObjectType(stdClass::class)),
        );

        $factory = new DocumentIdPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertiesWithNullNativeType(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new DocumentIdPropertyMetadataFactory($decorated);
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
