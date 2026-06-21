<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\InventoryReaderException;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;

use function iterator_to_array;

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

    public function testInventoryReaderTreatsBlankMatterCellsAsNull(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertNull($result[0]->getDocumentMetadata()?->getMatter());
        self::assertNull($result[1]->getException());
        self::assertNull($result[1]->getDocumentMetadata()?->getMatter());
    }

    public function testInventoryReaderWithoutMatterCellsDefaultsToNull(): void
    {
        $this->reader->open(__DIR__ . '/inventory-missing-matter.xlsx');

        $result = $this->reader->getDocumentMetadataGenerator(new WooDecision())->current();

        self::assertInstanceOf(InventoryReadItem::class, $result);
        self::assertNull($result->getDocumentMetadata()?->getMatter());
    }

    public function testInventoryReaderAddsExceptionsForTooLongRemark(): void
    {
        $this->reader->open(__DIR__ . '/inventory-remark-too-long.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forRemarkTooLong(3, 1000), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeFamilyId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-negative-family-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forInvalidFamilyId(3), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForNegativeThreadId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-negative-thread-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forInvalidThreadId(3), $result[1]->getException());
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
        self::assertEquals(InventoryReaderException::forInvalidDocumentId(3), $result[1]->getException());
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

        self::assertEquals(InventoryReaderException::forInvalidLink('not-a-valid-url', 2), $result[0]->getException());
    }

    public function testInventoryReaderAddsExceptionForInvalidLinkInRemarkColumn(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-link-in-remark.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forInvalidLink('http://invalid link because of spaces', 2), $result[0]->getException());
    }
}
