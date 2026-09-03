<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\OpenApi\Metadata\LandingPageTitlePropertyMetadataFactory;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class LandingPageTitlePropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItSetsStringSchemaForLandingPageTitleProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new ObjectType(LandingPageTitle::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new LandingPageTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertSame([
            'type' => 'string',
            'minLength' => 1,
            'maxLength' => 200,
        ], $result->getSchema());
    }

    public function testItSetsNullableSchemaForNullableLandingPageTitleProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new ObjectType(LandingPageTitle::class)),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: $propertyMetadata,
        );

        $factory = new LandingPageTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'title');

        self::assertSame([
            'anyOf' => [[
                'type' => 'string',
                'minLength' => 1,
                'maxLength' => 200,
            ], ['type' => 'null']],
        ], $result->getSchema());
    }

    public function testItDoesNotModifyNonLandingPageTitleProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'other',
            propertyMetadata: new ApiProperty()->withNativeType(new ObjectType(stdClass::class)),
        );

        $factory = new LandingPageTitlePropertyMetadataFactory($decorated);
        $result = $factory->create('TestClass', 'other');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertiesWithNullNativeType(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'title',
            propertyMetadata: new ApiProperty(),
        );

        $factory = new LandingPageTitlePropertyMetadataFactory($decorated);
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
