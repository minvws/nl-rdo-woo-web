<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\InventoryReaderException;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Tests\Unit\UnitTestCase;

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

        self::assertEquals(new \DateTimeImmutable('2023-11-04'), $documentMetadata1->getDate());
        self::assertEquals(new \DateTimeImmutable('2023-05-06'), $documentMetadata2->getDate());

        self::assertEquals(['http://foo.bar'], $documentMetadata1->getLinks());
        self::assertEquals(['http://foo.bar/baz'], $documentMetadata2->getLinks());

        self::assertEquals('test remark', $documentMetadata1->getRemark());
        self::assertEquals(null, $documentMetadata2->getRemark());

        self::assertEquals(SourceType::UNKNOWN, $documentMetadata1->getSourceType());
        self::assertEquals(SourceType::UNKNOWN, $documentMetadata2->getSourceType());
    }

    public function testInventoryReaderAddsExceptionsForEmptyMatterCells(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forInvalidMatterInRow(2, 2, 50), $result[0]->getException());
        self::assertEquals(InventoryReaderException::forInvalidMatterInRow(3, 2, 50), $result[1]->getException());
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

    public function testInventoryReaderAddsExceptionsForSingleCharacterMatterCells(): void
    {
        $this->reader->open(__DIR__ . '/inventory-single-character-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forInvalidMatterInRow(2, 2, 50), $result[0]->getException());
        self::assertEquals(InventoryReaderException::forInvalidMatterInRow(3, 2, 50), $result[1]->getException());
    }

    public function testInventoryWithEmptyDates(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-date.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        $documentMetadata1 = $result[0]->getDocumentMetadata();
        $documentMetadata2 = $result[1]->getDocumentMetadata();
        self::assertNotNull($documentMetadata1);
        self::assertNotNull($documentMetadata2);

        self::assertEquals(new \DateTimeImmutable('2023-11-04'), $documentMetadata1->getDate());
        self::assertEquals(null, $documentMetadata2->getDate());
    }

    public function testInventoryReaderAddsExceptionForInvalidDocumentId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-document-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertEquals(InventoryReaderException::forInvalidDocumentId(3), $result[1]->getException());
    }
}
