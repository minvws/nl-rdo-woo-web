<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use ArrayIterator;
use Mockery;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\Import\FilenameTooLongException;
use Shared\Exception\Import\InvalidDocumentIdException;
use Shared\Exception\Import\InvalidDocumentIdLengthException;
use Shared\Exception\Import\InvalidFamilyIdException;
use Shared\Exception\Import\InvalidInquiryNumbersException;
use Shared\Exception\Import\InvalidLinkException;
use Shared\Exception\Import\InvalidPublicationContextException;
use Shared\Exception\Import\InvalidRefersToException;
use Shared\Exception\Import\InvalidThreadIdException;
use Shared\Exception\Import\LinkTooLongException;
use Shared\Exception\Import\MatterAndPublicationContextCombinationException;
use Shared\Exception\Import\MissingDocumentIdException;
use Shared\Exception\Import\MissingMatterAndPublicationContextException;
use Shared\Exception\Import\RemarkTooLongException;
use Shared\Exception\Import\RowProcessingException;
use Shared\Service\FileReader\CsvReaderFactory;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\FileReader\FileReaderInterface;
use Shared\Service\FileReader\ReaderFactoryInterface;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\MetadataField;
use Shared\Service\Inventory\Reader\InventoryReader;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;

use function iterator_to_array;
use function sprintf;
use function str_repeat;

class InventoryReaderTest extends UnitTestCase
{
    private InventoryReaderInterface $reader;

    protected function setUp(): void
    {
        $factory = new InventoryReaderFactory([
            new ExcelReaderFactory(),
        ]);

        $this->reader = $factory->create('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        parent::setUp();
    }

    public function testAreLinkAndRemarkParsedCorrectly(): void
    {
        $dossier = new WooDecision();

        $this->reader->open(__DIR__ . '/inventory-link-remark-1.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-2.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-3.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals([], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-4.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-5.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testInventoryWithNewFormatDescribedInWoo1645(): void
    {
        $this->reader->open(__DIR__ . '/inventory-1645.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        $documentMetadata1 = $result[0]->getDocumentMetadata();
        $documentMetadata2 = $result[1]->getDocumentMetadata();
        self::assertNotNull($documentMetadata1);
        self::assertNotNull($documentMetadata2);

        self::assertEquals(PlainDate::create('2023-11-04'), $documentMetadata1->getDate());
        self::assertEquals(PlainDate::create('2023-05-06'), $documentMetadata2->getDate());

        self::assertEquals(['http://foo.bar'], $documentMetadata1->getLinks());
        self::assertEquals(['http://foo.bar/baz'], $documentMetadata2->getLinks());

        self::assertEquals('test remark', $documentMetadata1->getRemark());
        self::assertEquals(null, $documentMetadata2->getRemark());

        self::assertEquals(SourceType::UNKNOWN, $documentMetadata1->getSourceType());
        self::assertEquals(SourceType::UNKNOWN, $documentMetadata2->getSourceType());
    }

    public function testInventoryReaderAddsExceptionForMissingMatterAndPublicationContext(): void
    {
        $this->reader->open(__DIR__ . '/inventory-missing-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(
            new MissingMatterAndPublicationContextException(2),
            $result[0]->getException(),
        );
        self::assertEquals(
            new MissingMatterAndPublicationContextException(3),
            $result[1]->getException(),
        );
    }

    public function testInventoryReaderAddsExceptionForEmptyMatterAndPublicationContext(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-missing-matter-and-publication-context.csv');

        $result = $reader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(
            new MissingMatterAndPublicationContextException(1),
            $result->getException(),
        );
    }

    public function testInventoryReaderAddsExceptionsForTooLongRemark(): void
    {
        $this->reader->open(__DIR__ . '/inventory-remark-too-long.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new RemarkTooLongException(3, 1000), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeFamilyId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-negative-family-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidFamilyIdException(3), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeThreadId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-negative-thread-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidThreadIdException(3), $result[1]->getException());
    }

    public function testInventoryReaderAllowsSingleCharacterMatterCells(): void
    {
        $this->reader->open(__DIR__ . '/inventory-single-character-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertNull($result[1]->getException());
    }

    public function testInventoryWithEmptyDates(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-date.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        $documentMetadata1 = $result[0]->getDocumentMetadata();
        $documentMetadata2 = $result[1]->getDocumentMetadata();
        self::assertNotNull($documentMetadata1);
        self::assertNotNull($documentMetadata2);

        self::assertEquals(PlainDate::create('2023-11-04'), $documentMetadata1->getDate());
        self::assertEquals(null, $documentMetadata2->getDate());
    }

    public function testInventoryReaderAddsExceptionForInvalidDocumentId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-document-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertEquals(new InvalidDocumentIdException(3), $result[1]->getException());
    }

    public function testInventoryReaderSplitsRemarkWithMultipleUrls(): void
    {
        $this->reader->open(__DIR__ . '/inventory-link-remark-6.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertNotNull($result[0]->getDocumentMetaData());
        self::assertEquals(
            ['https://example.org', 'https://test.org'],
            $result[0]->getDocumentMetaData()->getLinks(),
        );
        self::assertNull($result[0]->getDocumentMetaData()->getRemark());
    }

    public function testInventoryReaderAddsExceptionForInvalidLinkInLinkColumn(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-link.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidLinkException('not-a-valid-url', 2), $result[0]->getException());
    }

    public function testInventoryReaderAddsExceptionForInvalidLinkInRemarkColumn(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-link-in-remark.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidLinkException('http://invalid link because of spaces', 2), $result[0]->getException());
    }

    public function testInventoryReaderParsesPublicationContextColumn(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-publicationcontext.csv');

        $result = $reader->getDocumentMetadataGenerator(new WooDecision())->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getException());
        self::assertSame(
            'publicatiecontext-1',
            $result->getDocumentMetadata()?->getPublicationContext()?->toString(),
        );
    }

    public function testInventoryReaderRejectsMatterAndPublicationContextColumnsTogether(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-publicationcontext-with-matter.csv');

        $result = $reader->getDocumentMetadataGenerator(new WooDecision())->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertEquals(
            new MatterAndPublicationContextCombinationException(),
            $result->getException(),
        );
    }

    public function testInventoryReaderAcceptsEmptyMatterWhenPublicationContextIsFilled(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-publicationcontext-with-empty-matter.csv');

        $result = $reader->getDocumentMetadataGenerator(new WooDecision())->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getException());
        self::assertSame(
            'publicatiecontext-3',
            $result->getDocumentMetadata()?->getPublicationContext()?->toString(),
        );
    }

    public function testInventoryReaderExpandsRelatedIdsWhenTheRowHasAMatter(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(sprintf('%s/inventory-matter-with-related-id.csv', __DIR__));

        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('PREF');

        $result = $reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getException());
        self::assertSame(
            ['pref-xx-1004', 'pref-yy-1005', 'pref-zz-1006'],
            $result->getDocumentMetadata()?->getRefersTo(),
        );
    }

    public function testInventoryReaderAddsExceptionForInvalidRelatedIdWhenTheRowHasAMatter(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(sprintf('%s/inventory-matter-with-invalid-related-id.csv', __DIR__));

        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('PREF');

        $result = $reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getDocumentMetadata());
        self::assertEquals(new InvalidRefersToException('1004 | 1005', 1), $result->getException());
    }

    public function testInventoryReaderKeepsRelatedIdsWhenTheRowHasAPublicationContext(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(sprintf('%s/inventory-publicationcontext-with-related-id.csv', __DIR__));

        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('PREF');

        $result = $reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getException());
        self::assertSame(
            ['SOME-OTHER-CONTEXT-1008'],
            $result->getDocumentMetadata()?->getRefersTo(),
        );
    }

    public function testInventoryReaderAcceptsAlternativeColumnNamesForDateAndGround(): void
    {
        $this->reader->open(sprintf('%s/inventory-alternative-date-and-ground.xlsx', __DIR__));

        $iterator = $this->reader->getDocumentMetadataGenerator(new WooDecision());

        $documentMetadata = $iterator->current()->getDocumentMetadata();
        self::assertInstanceOf(DocumentMetadata::class, $documentMetadata);

        self::assertEquals(PlainDate::create('2022-10-09'), $documentMetadata->getDate());
        self::assertEquals(['5.1.2e'], $documentMetadata->getGrounds());
    }

    public function testHasMatterWhenColumnExists(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-matter-with-related-id.csv');

        self::assertTrue($reader->hasMatterColumn());
    }

    public function testHasMatterWhenColumnDoesntExist(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(__DIR__ . '/inventory-publicationcontext.csv');

        self::assertFalse($reader->hasMatterColumn());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenDocumentIdIsMissing(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $filename = $this->getFaker()->word();

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn('');

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($filepath)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new MissingDocumentIdException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenDocumentIdIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->numerify(str_repeat('#', 171));

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($filepath)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidDocumentIdLengthException($rowIndex, 1, 170), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenFilenameIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $filename = $this->getFaker()->lexify(str_repeat('?', 501));

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($filepath)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new FilenameTooLongException($filename, $rowIndex, 500), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenInquiryNumbersAreInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->word();
        $inquiryNumbers = sprintf('<%s>', $this->getFaker()->word());

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturn($inquiryNumbers);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($filepath)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidInquiryNumbersException($inquiryNumbers, $rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenPublicationContextIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = sprintf('%s %s', $this->getFaker()->word(), $this->getFaker()->word());
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->word();

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader
            ->expects('getOptionalString')
            ->twice()
            ->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)
            ->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($filepath)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidPublicationContextException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenPublicationContextFromMatterIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $matter = $this->getFaker()->lexify(str_repeat('?', 255));
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->word();

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->times(3)->with($rowIndex, MetadataField::MATTER->value)->andReturn($matter);
        $fileReader->expects('getOptionalString')->twice()->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($filepath)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidPublicationContextException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenLinkIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $link = sprintf('https://%s/%s', $this->getFaker()->domainName(), $this->getFaker()->lexify(str_repeat('?', 2048)));

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturn($link);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($filepath)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new LinkTooLongException($link, $rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenRefersToIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();
        $matter = $this->getFaker()->lexify();
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->word();
        $refersTo = sprintf('%s %s', $this->getFaker()->word(), $this->getFaker()->word());

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->times(4)->with($rowIndex, MetadataField::MATTER->value)->andReturn($matter);
        $fileReader->expects('getOptionalString')->twice()->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalInt')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REFERS_TO->value)->andReturn($refersTo);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($filepath)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidRefersToException($refersTo, $rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenRowCannotBeProcessed(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $filepath = $this->getFaker()->word();

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andThrow(new RuntimeException());

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($filepath)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($filepath);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        $exception = $inventoryReadItem->getException();

        self::assertInstanceOf(RowProcessingException::class, $exception);
        self::assertSame($rowIndex, $exception->rowIndex);
    }
}
