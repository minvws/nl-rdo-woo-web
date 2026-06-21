<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Domain\OpenApi\Metadata\MainDocumentAttachmentTypePropertyMetadataFactory;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MainDocumentAttachmentTypePropertyMetadataFactoryTest extends UnitTestCase
{
    /**
     * AnnualReport allows ANNUAL_REPORT and ANNUAL_PLAN.
     * The factory reads these from AnnualReportMainDocumentRequestDto::getAllowedTypes().
     */
    public function testItSetsRestrictedEnumSchemaForMultiTypeDossier(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(new EnumType(AttachmentType::class));

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'type');

        $schema = $result->getSchema();
        self::assertIsArray($schema);
        self::assertSame('string', $schema['type']);
        self::assertIsArray($schema['enum']);
        self::assertContains(AttachmentType::ANNUAL_REPORT->value, $schema['enum']);
        self::assertContains(AttachmentType::ANNUAL_PLAN->value, $schema['enum']);
        self::assertCount(2, $schema['enum']);
    }

    /**
     * AnnualReport allows ANNUAL_REPORT and ANNUAL_PLAN — varnames must match enum order.
     */
    public function testItSetsFilteredVarnamesMatchingRestrictedEnum(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(new EnumType(AttachmentType::class));

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'type');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
        $varnames = $openapiContext['x-enum-varnames'];
        self::assertIsArray($varnames);
        // Must have exactly 2 labels (one per allowed type), not 59 (all types)
        self::assertCount(2, $varnames);
        self::assertContains('annual_report_label', $varnames);
        self::assertContains('annual_plan_label', $varnames);
    }

    /**
     * The factory reads the allowed type from CovenantMainDocumentRequestDto::getAllowedTypes().
     */
    public function testItSetsNullableRestrictedEnumSchemaForSingleTypeDossier(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new EnumType(AttachmentType::class)),
        );

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: CovenantMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(CovenantMainDocumentRequestDto::class, 'type');

        $schema = $result->getSchema();
        self::assertIsArray($schema);
        self::assertArrayHasKey('anyOf', $schema);
        $anyOf = $schema['anyOf'];
        self::assertIsArray($anyOf);
        self::assertCount(2, $anyOf);
        self::assertSame(['type' => 'string', 'enum' => [AttachmentType::COVENANT->value]], $anyOf[0]);
        self::assertSame(['type' => 'null'], $anyOf[1]);
    }

    /**
     * Covenant has 1 allowed type — varnames must have exactly 1 label.
     */
    public function testItSetsFilteredVarnamesForNullableSingleTypeDossier(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(
            new NullableType(new EnumType(AttachmentType::class)),
        );

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: CovenantMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(CovenantMainDocumentRequestDto::class, 'type');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
        $varnames = $openapiContext['x-enum-varnames'];
        self::assertIsArray($varnames);
        self::assertCount(1, $varnames);
        self::assertContains('covenant_label', $varnames);
    }

    /**
     * Pre-existing openapiContext keys (e.g., from inner decorators) are preserved.
     */
    public function testItPreservesExistingOpenapiContextKeys(): void
    {
        $propertyMetadata = new ApiProperty()
            ->withNativeType(new EnumType(AttachmentType::class))
            ->withOpenapiContext(['description' => 'The document type']);

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'type');

        $openapiContext = $result->getOpenapiContext();
        self::assertNotNull($openapiContext);
        self::assertSame('The document type', $openapiContext['description']);
        self::assertArrayHasKey('x-enum-varnames', $openapiContext);
    }

    public function testItDoesNotModifyNonTypeProperty(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(new EnumType(AttachmentType::class));

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'language',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'language');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyNonMainDocumentDtoClass(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(new EnumType(AttachmentType::class));

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: stdClass::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(stdClass::class, 'type');

        // stdClass does not implement MainDocumentDtoInterface, so no restriction is applied.
        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyPropertyWithNullNativeType(): void
    {
        $propertyMetadata = new ApiProperty();

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'type');

        self::assertNull($result->getSchema());
    }

    public function testItDoesNotModifyTypePropertyWithNonAttachmentTypeEnum(): void
    {
        $propertyMetadata = new ApiProperty()->withNativeType(new ObjectType(stdClass::class));

        $factory = new MainDocumentAttachmentTypePropertyMetadataFactory(
            $this->createMockedPropertyMetadataFactory(
                resourceClass: AnnualReportMainDocumentRequestDto::class,
                property: 'type',
                propertyMetadata: $propertyMetadata,
            ),
            $this->createMockedTranslator(),
        );
        $result = $factory->create(AnnualReportMainDocumentRequestDto::class, 'type');

        self::assertNull($result->getSchema());
    }

    /**
     * @param class-string $resourceClass
     */
    private function createMockedPropertyMetadataFactory(
        string $resourceClass,
        string $property,
        ApiProperty $propertyMetadata,
    ): PropertyMetadataFactoryInterface&MockInterface {
        $mock = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $mock->expects('create')
            ->with($resourceClass, $property, [])
            ->andReturn($propertyMetadata);

        return $mock;
    }

    private function createMockedTranslator(): TranslatorInterface&MockInterface
    {
        $mock = Mockery::mock(TranslatorInterface::class);
        $mock->allows('trans')->andReturnUsing(
            static fn (string $id): string => match ($id) {
                'annual_report' => 'annual_report_label',
                'annual_plan' => 'annual_plan_label',
                'covenant' => 'covenant_label',
                default => $id,
            },
        );

        return $mock;
    }
}
