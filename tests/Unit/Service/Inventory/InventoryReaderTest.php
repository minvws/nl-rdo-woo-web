<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Dossier;
use App\Service\Excel\ExcelReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InventoryReaderTest extends MockeryTestCase
{
    private InventoryReaderInterface $reader;

    public function setUp(): void
    {
        $factory = new InventoryReaderFactory(
            new ExcelReaderFactory()
        );

        $this->reader = $factory->create();

        parent::setUp();
    }

    public function testAreLinkAndRemarkParsedCorrectly(): void
    {
        $dossier = new Dossier();

        $this->reader->open(__DIR__ . '/inventory-link-remark-1.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-2.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-3.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertNull($item->getDocumentMetaData()->getLink());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-4.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $this->reader->open(__DIR__ . '/inventory-link-remark-5.xlsx');
        $item = $this->reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://example.org', $item->getDocumentMetaData()->getLink());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testAreDefaultSubjectsSet(): void
    {
        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $this->reader->open(__DIR__ . '/inventory-subjects-1.xlsx');

        $result = iterator_to_array($this->reader->getDocumentMetadataGenerator($dossier));
        $this->assertEquals(['subject 1', 'subject 2'], $result[0]->getDocumentMetadata()->getSubjects());
        $this->assertEquals(['foo', 'bar'], $result[1]->getDocumentMetadata()->getSubjects());
    }
}
