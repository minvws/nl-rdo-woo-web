<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Dossier;
use App\Exception\InventoryReaderException;
use App\Service\FileReader\ExcelReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use App\SourceType;
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
        $dossier = new Dossier();

        $this->reader->open(__DIR__ . '/inventory-link-remark-1.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-2.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-3.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals([], $item->getDocumentMetaData()->getLinks());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-4.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals(['https://www.example.org'], $item->getDocumentMetaData()->getLinks());
        $this->assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-5.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals(['https://example.org'], $item->getDocumentMetaData()->getLinks());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testAreDefaultSubjectsUsedForAllDocuments(): void
    {
        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $this->reader->open(__DIR__ . '/inventory-subjects-1.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));
        $this->assertEquals(['foo', 'bar'], $result[0]->getDocumentMetadata()->getSubjects());
        $this->assertEquals(['foo', 'bar'], $result[1]->getDocumentMetadata()->getSubjects());
    }

    public function testInventoryWithNewFormatDescribedInWoo1645(): void
    {
        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $this->reader->open(__DIR__ . '/inventory-1645.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));

        $this->assertEquals(['foo', 'bar'], $result[0]->getDocumentMetadata()->getSubjects());
        $this->assertEquals(['foo', 'bar'], $result[1]->getDocumentMetadata()->getSubjects());

        $this->assertEquals(new \DateTimeImmutable('2023-11-04'), $result[0]->getDocumentMetadata()->getDate());
        $this->assertEquals(new \DateTimeImmutable('2023-05-06'), $result[1]->getDocumentMetadata()->getDate());

        $this->assertEquals(['http://foo.bar'], $result[0]->getDocumentMetaData()->getLinks());
        $this->assertEquals(['http://foo.bar/baz'], $result[1]->getDocumentMetaData()->getLinks());

        $this->assertEquals('test remark', $result[0]->getDocumentMetaData()->getRemark());
        $this->assertEquals(null, $result[1]->getDocumentMetaData()->getRemark());

        $this->assertEquals(SourceType::SOURCE_UNKNOWN, $result[0]->getDocumentMetaData()->getSourceType());
        $this->assertEquals(SourceType::SOURCE_UNKNOWN, $result[1]->getDocumentMetaData()->getSourceType());
    }

    public function testInventoryReaderAddsExceptionsForEmptyMatterCells(): void
    {
        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $this->reader->open(__DIR__ . '/inventory-empty-matter.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));

        $this->assertEquals(InventoryReaderException::forMissingMatterInRow(2), $result[0]->getException());
        $this->assertEquals(InventoryReaderException::forMissingMatterInRow(3), $result[1]->getException());
    }

    public function testInventoryWithEmptyDates(): void
    {
        $dossier = new Dossier();

        $this->reader->open(__DIR__ . '/inventory-empty-date.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));

        $this->assertEquals(new \DateTimeImmutable('2023-11-04'), $result[0]->getDocumentMetadata()->getDate());
        $this->assertEquals(null, $result[1]->getDocumentMetadata()->getDate());
    }

    public function testInventoryReaderAddsExceptionForInvalidDocumentId(): void
    {
        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $this->reader->open(__DIR__ . '/inventory-invalid-document-id.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));

        $this->assertNull($result[0]->getException());
        $this->assertEquals(InventoryReaderException::forInvalidDocumentId(3), $result[1]->getException());
    }
}
