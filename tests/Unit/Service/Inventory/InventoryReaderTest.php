<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\SourceType;
use App\Exception\InventoryReaderException;
use App\Service\FileReader\ExcelReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InventoryReaderTest extends MockeryTestCase
{
    private InventoryReaderInterface $reader;

    public function setUp(): void
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
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-2.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-3.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertEquals([], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-4.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-5.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        self::assertEquals(['https://example.org'], $item->getDocumentMetaData()->getLinks());
        self::assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testInventoryWithNewFormatDescribedInWoo1645(): void
    {
        $this->reader->open(__DIR__ . '/inventory-1645.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new \DateTimeImmutable('2023-11-04'), $result[0]->getDocumentMetadata()->getDate());
        self::assertEquals(new \DateTimeImmutable('2023-05-06'), $result[1]->getDocumentMetadata()->getDate());

        self::assertEquals(['http://foo.bar'], $result[0]->getDocumentMetaData()->getLinks());
        self::assertEquals(['http://foo.bar/baz'], $result[1]->getDocumentMetaData()->getLinks());

        self::assertEquals('test remark', $result[0]->getDocumentMetaData()->getRemark());
        self::assertEquals(null, $result[1]->getDocumentMetaData()->getRemark());

        self::assertEquals(SourceType::UNKNOWN, $result[0]->getDocumentMetaData()->getSourceType());
        self::assertEquals(SourceType::UNKNOWN, $result[1]->getDocumentMetaData()->getSourceType());
    }

    public function testInventoryReaderAddsExceptionsForEmptyMatterCells(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forMissingMatterInRow(2), $result[0]->getException());
        self::assertEquals(InventoryReaderException::forMissingMatterInRow(3), $result[1]->getException());
    }

    public function testInventoryReaderAddsExceptionsForSingleCharacterMatterCells(): void
    {
        $this->reader->open(__DIR__ . '/inventory-single-character-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(InventoryReaderException::forMissingMatterInRow(2), $result[0]->getException());
        self::assertEquals(InventoryReaderException::forMissingMatterInRow(3), $result[1]->getException());
    }

    public function testInventoryWithEmptyDates(): void
    {
        $this->reader->open(__DIR__ . '/inventory-empty-date.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertEquals(new \DateTimeImmutable('2023-11-04'), $result[0]->getDocumentMetadata()->getDate());
        self::assertEquals(null, $result[1]->getDocumentMetadata()->getDate());
    }

    public function testInventoryReaderAddsExceptionForInvalidDocumentId(): void
    {
        $this->reader->open(__DIR__ . '/inventory-invalid-document-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator(new WooDecision()), false);

        self::assertNull($result[0]->getException());
        self::assertEquals(InventoryReaderException::forInvalidDocumentId(3), $result[1]->getException());
    }
}
