<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use ArrayIterator;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\Import\FilenameTooLongException;
use Shared\Exception\Import\InvalidDocumentIdException;
use Shared\Exception\Import\InvalidDocumentIdLengthException;
use Shared\Exception\Import\InvalidFamilyIdException;
use Shared\Exception\Import\InvalidInquiryNumbersException;
use Shared\Exception\Import\InvalidJudgementException;
use Shared\Exception\Import\InvalidLinkException;
use Shared\Exception\Import\InvalidPublicationContextException;
use Shared\Exception\Import\InvalidRefersToException;
use Shared\Exception\Import\InvalidThreadIdException;
use Shared\Exception\Import\LinkTooLongException;
use Shared\Exception\Import\MatterAndPublicationContextCombinationException;
use Shared\Exception\Import\MissingDocumentIdException;
use Shared\Exception\Import\MissingMatterAndPublicationContextException;
use Shared\Exception\Import\RemarkTooLongException;
use Shared\Exception\Import\UnexpectedRowException;
use Shared\Service\FileReader\CsvReaderFactory;
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
            new CsvReaderFactory(),
        ]);

        $this->reader = $factory->create('text/csv');

        parent::setUp();
    }

    public function testAreLinkAndRemarkParsedCorrectly(): void
    {
        $dossier = new WooDecision();

        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-1.csv', __DIR__), 'r'));
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-2.csv', __DIR__), 'r'));
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-3.csv', __DIR__), 'r'));
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals([], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-4.csv', __DIR__), 'r'));
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-5.csv', __DIR__), 'r'));
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertNotNull($item->getDocumentMetaData());
        self::assertEquals(['https://example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testInventoryWithNewFormatDescribedInWoo1645(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-1645.csv', __DIR__), 'r'));

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
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-missing-matter.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(
            new MissingMatterAndPublicationContextException(1),
            $result[0]->getException(),
        );
        self::assertEquals(
            new MissingMatterAndPublicationContextException(2),
            $result[1]->getException(),
        );
    }

    public function testInventoryReaderAddsExceptionForEmptyMatterAndPublicationContext(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(
            Utils::tryFopen(sprintf('%s/inventory-missing-matter-and-publication-context.csv', __DIR__), 'r'),
        );

        $result = $reader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(
            new MissingMatterAndPublicationContextException(1),
            $result->getException(),
        );
    }

    public function testInventoryReaderAddsExceptionsForTooLongRemark(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-remark-too-long.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new RemarkTooLongException(2, 1000), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeFamilyId(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-negative-family-id.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidFamilyIdException(2), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeThreadId(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-negative-thread-id.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidThreadIdException(2), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionForInvalidJudgement(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-invalid-judgement.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertSame(Judgement::PARTIAL_PUBLIC, $result[0]->getDocumentMetadata()?->getJudgement());

        self::assertEquals(new InvalidJudgementException(2, 'foobar'), $result[1]->getException());
        self::assertNull($result[1]->getDocumentMetadata());

        self::assertNull($result[2]->getException());
        self::assertSame(Judgement::NOT_PUBLIC, $result[2]->getDocumentMetadata()?->getJudgement());
    }

    public function testInventoryReaderValidatesFamilyAndThreadIds(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-family-and-thread-ids.csv', __DIR__), 'r'));

        $result = iterator_to_array($reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        $documentMetadata = $result[0]->getDocumentMetadata();
        self::assertNotNull($documentMetadata);
        self::assertSame(12, $documentMetadata->getFamilyId());
        self::assertSame(34, $documentMetadata->getThreadId());

        self::assertNull($result[1]->getException());
        $documentMetadata = $result[1]->getDocumentMetadata();
        self::assertNotNull($documentMetadata);
        self::assertNull($documentMetadata->getFamilyId());
        self::assertNull($documentMetadata->getThreadId());

        self::assertEquals(new InvalidFamilyIdException(3), $result[2]->getException());
        self::assertEquals(new InvalidFamilyIdException(4), $result[3]->getException());
        self::assertEquals(new InvalidThreadIdException(5), $result[4]->getException());
        self::assertEquals(new InvalidThreadIdException(6), $result[5]->getException());
    }

    public function testInventoryReaderAllowsSingleCharacterMatterCells(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-single-character-matter.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertNull($result[1]->getException());
    }

    public function testInventoryWithEmptyDates(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-empty-date.csv', __DIR__), 'r'));

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
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-invalid-document-id.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertEquals(new InvalidDocumentIdException(2), $result[1]->getException());
    }

    public function testInventoryReaderSplitsRemarkWithMultipleUrls(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-link-remark-6.csv', __DIR__), 'r'));

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
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-invalid-link.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidLinkException(1, 'not-a-valid-url'), $result[0]->getException());
    }

    public function testInventoryReaderAddsExceptionForInvalidLinkInRemarkColumn(): void
    {
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-invalid-link-in-remark.csv', __DIR__), 'r'));

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new InvalidLinkException(1, 'http://invalid link because of spaces'), $result[0]->getException());
    }

    public function testInventoryReaderParsesPublicationContextColumn(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-publicationcontext.csv', __DIR__), 'r'));

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

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-publicationcontext-with-matter.csv', __DIR__), 'r'));

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

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-publicationcontext-with-empty-matter.csv', __DIR__), 'r'));

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

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-matter-with-related-id.csv', __DIR__), 'r'));

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

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-matter-with-invalid-related-id.csv', __DIR__), 'r'));

        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('PREF');

        $result = $reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getDocumentMetadata());
        self::assertEquals(new InvalidRefersToException(1, '1004 | 1005'), $result->getException());
    }

    public function testInventoryReaderKeepsRelatedIdsWhenTheRowHasAPublicationContext(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-publicationcontext-with-related-id.csv', __DIR__), 'r'));

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
        $this->reader->open(Utils::tryFopen(sprintf('%s/inventory-alternative-date-and-ground.csv', __DIR__), 'r'));

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

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-matter-with-related-id.csv', __DIR__), 'r'));

        self::assertTrue($reader->hasMatterColumn());
    }

    public function testHasMatterWhenColumnDoesntExist(): void
    {
        $factory = new InventoryReaderFactory([
            new CsvReaderFactory(),
        ]);
        $reader = $factory->create('text/csv');

        $reader->open(Utils::tryFopen(sprintf('%s/inventory-publicationcontext.csv', __DIR__), 'r'));

        self::assertFalse($reader->hasMatterColumn());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenDocumentIdIsMissing(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
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
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn('');

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($stream)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new MissingDocumentIdException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenDocumentIdIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
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
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($stream)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidDocumentIdLengthException($rowIndex, 1, 170), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenFilenameIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
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
        $readerFactory->expects('createReader')->with($stream)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new FilenameTooLongException($rowIndex, $filename, 500), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenInquiryNumbersAreInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->randomElement(['openbaar', 'deels openbaar', 'reeds openbaar', 'niet openbaar']);
        $inquiryNumbers = sprintf('<%s>', $this->getFaker()->word());

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturn($inquiryNumbers);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($stream)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidInquiryNumbersException($rowIndex, $inquiryNumbers), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenPublicationContextIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $publicationContext = sprintf('%s %s', $this->getFaker()->word(), $this->getFaker()->word());
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->randomElement(['openbaar', 'deels openbaar', 'reeds openbaar', 'niet openbaar']);

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
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')->with($stream)->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidPublicationContextException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenPublicationContextFromMatterIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $matter = $this->getFaker()->lexify(str_repeat('?', 255));
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->randomElement(['openbaar', 'deels openbaar', 'reeds openbaar', 'niet openbaar']);

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->times(3)->with($rowIndex, MetadataField::MATTER->value)->andReturn($matter);
        $fileReader->expects('getOptionalString')->twice()->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($stream)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidPublicationContextException($rowIndex), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenLinkIsTooLong(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $publicationContext = $this->getFaker()->publicationContext()->toString();
        $link = sprintf('https://%s/%s', $this->getFaker()->domainName(), $this->getFaker()->lexify(str_repeat('?', 2048)));

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturn($publicationContext);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturn($link);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($stream)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new LinkTooLongException($rowIndex, $link, 2048), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenRefersToIsInvalid(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $matter = $this->getFaker()->lexify();
        $filename = $this->getFaker()->word();
        $documentId = $this->getFaker()->documentId()->toString();
        $judgement = $this->getFaker()->randomElement(['openbaar', 'deels openbaar', 'reeds openbaar', 'niet openbaar']);
        $refersTo = sprintf('%s %s', $this->getFaker()->word(), $this->getFaker()->word());

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->times(4)->with($rowIndex, MetadataField::MATTER->value)->andReturn($matter);
        $fileReader->expects('getOptionalString')->twice()->with($rowIndex, MetadataField::PUBLICATION_CONTEXT->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::LINK->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REMARK->value)->andReturnNull();
        $fileReader->expects('getOptionalDateTime')->with($rowIndex, MetadataField::DATE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::DOCUMENT->value)->andReturn($filename);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::FAMILY->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SOURCETYPE->value)->andReturnNull();
        $fileReader->expects('getString')->with($rowIndex, MetadataField::GROUND->value)->andReturn('');
        $fileReader->expects('getString')->with($rowIndex, MetadataField::ID->value)->andReturn($documentId);
        $fileReader->expects('getString')->with($rowIndex, MetadataField::JUDGEMENT->value)->andReturn($judgement);
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::THREADID->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::INQUIRY_NUMBER->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::SUSPENDED->value)->andReturnNull();
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::REFERS_TO->value)->andReturn($refersTo);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($stream)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertEquals(new InvalidRefersToException($rowIndex, $refersTo), $inventoryReadItem->getException());
    }

    public function testGetDocumentMetadataGeneratorFailsWhenRowCannotBeProcessed(): void
    {
        $rowIndex = $this->getFaker()->numberBetween(1, 100);
        $stream = Utils::tryFopen('php://memory', 'r');
        $runtimeException = new RuntimeException();

        $fileReader = Mockery::mock(FileReaderInterface::class);
        $fileReader->expects('getIterator')->andReturn(new ArrayIterator([$rowIndex => []]));
        $fileReader->expects('getOptionalString')->with($rowIndex, MetadataField::MATTER->value)->andThrow($runtimeException);

        $readerFactory = Mockery::mock(ReaderFactoryInterface::class);
        $readerFactory->expects('createReader')
            ->with($stream)
            ->andReturn($fileReader);

        $inventoryReader = new InventoryReader($readerFactory);
        $inventoryReader->open($stream);

        $inventoryReadItem = $inventoryReader->getDocumentMetadataGenerator(new WooDecision())->current();

        $exception = $inventoryReadItem->getException();

        self::assertSame($rowIndex, $inventoryReadItem->getIndex());
        self::assertInstanceOf(UnexpectedRowException::class, $exception);
        self::assertSame($runtimeException, $exception->getPrevious());
    }
}
