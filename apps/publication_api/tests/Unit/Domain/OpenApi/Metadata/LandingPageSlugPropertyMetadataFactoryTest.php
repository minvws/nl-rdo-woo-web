<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\LandingPageSlugPropertyMetadataFactory;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class LandingPageSlugPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForLandingPageSlugProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(LandingPageSlug::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'slug',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new LandingPageSlugPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'slug');

        self::assertSame([
            'type' => 'string',
            'minLength' => 2,
            'maxLength' => 50,
            'pattern' => '^[A-Za-z0-9-]+$',
        ], $result->getSchema());
    }

    public function testItSetsNullableSchemaForNullableLandingPageSlugProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new ObjectType(LandingPageSlug::class)),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'slug',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new LandingPageSlugPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'slug');

        self::assertSame([
            'anyOf' => [[
                'type' => 'string',
                'minLength' => 2,
                'maxLength' => 50,
                'pattern' => '^[A-Za-z0-9-]+$',
            ], ['type' => 'null']],
        ], $result->getSchema());
    }

    public function testItDoesNotModifyNonLandingPageSlugProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'other',
            propertyMetadata: new ApiProperty()->withNativeType(new ObjectType(stdClass::class)),
        );

        $factory = new LandingPageSlugPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'other');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertiesWithNullNativeType(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'slug',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new LandingPageSlugPropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'slug');

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
