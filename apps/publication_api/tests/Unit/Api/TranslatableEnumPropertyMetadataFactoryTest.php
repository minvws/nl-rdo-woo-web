<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\TranslatableEnumPropertyMetadataFactory;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;

final class TranslatableEnumPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItAddsOpenapiContextForAttachmentLanguageProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new EnumType(AttachmentLanguage::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'language',
            propertyMetadata: $propertyMetadata,
        );

        $translator = $this->createMockedTranslator();
        $factory = new TranslatableEnumPropertyMetadataFactory($decorated, $translator);

        $result = $factory->create('TestClass', 'language');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
        $varnames = $openapiContext['x-enum-varnames'];
        self::assertIsArray($varnames);
        self::assertCount(count(AttachmentLanguage::cases()), $varnames);
    }

    public function testItAddsOpenapiContextForAttachmentTypeProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new EnumType(AttachmentType::class),
        );

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'type',
            propertyMetadata: $propertyMetadata,
        );

        $translator = $this->createMockedTranslator();
        $factory = new TranslatableEnumPropertyMetadataFactory($decorated, $translator);

        $result = $factory->create('TestClass', 'type');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
        $varnames = $openapiContext['x-enum-varnames'];
        self::assertIsArray($varnames);
        self::assertCount(count(AttachmentType::cases()), $varnames);
    }

    public function testItDoesNotModifyNonTranslatableEnumProperties(): void
    {
        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'externalId',
            propertyMetadata: new ApiProperty(),
        );

        $translator = $this->createMockedTranslator();
        $factory = new TranslatableEnumPropertyMetadataFactory($decorated, $translator);

        $result = $factory->create('TestClass', 'externalId');

        $openapiContext = $result->getOpenapiContext();
        self::assertNull($openapiContext);
    }

    public function testItDoesNotOverrideExistingOpenapiContext(): void
    {
        $existingContext = ['description' => 'The language of the attachment'];
        $propertyMetadata = new ApiProperty()
            ->withNativeType(new EnumType(AttachmentLanguage::class))
            ->withOpenapiContext($existingContext);

        $decorated = $this->createMockedPropertyMetadataFactory(
            property: 'language',
            propertyMetadata: $propertyMetadata,
        );

        $translator = $this->createMockedTranslator();
        $factory = new TranslatableEnumPropertyMetadataFactory($decorated, $translator);

        $result = $factory->create('TestClass', 'language');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertSame('The language of the attachment', $openapiContext['description']);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
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

    private function createMockedTranslator(): TranslatorInterface
    {
        $mock = Mockery::mock(TranslatorInterface::class);
        $mock->allows('trans')->andReturnUsing(
            static fn (string $id) => match ($id) {
                'nld' => 'Nederlands',
                'eng' => 'Engels',
                'deu' => 'Duits',
                'fra' => 'Frans',
                'advice' => 'Advies',
                'request_for_advice' => 'Verzoek om advies',
                default => $id,
            },
        );

        return $mock;
    }
}
