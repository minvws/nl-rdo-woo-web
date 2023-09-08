<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Dossier;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InventoryReaderTest extends MockeryTestCase
{
    public function testAreLinkAndRemarkParsedCorrectly(): void
    {
        $factory = new InventoryReaderFactory();
        $reader = $factory->create();

        $dossier = new Dossier();

        $reader->open(__DIR__ . '/inventory-link-remark-1.xlsx');
        $item = $reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $reader->open(__DIR__ . '/inventory-link-remark-2.xlsx');
        $item = $reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertNull($item->getDocumentMetaData()->getRemark());

        $reader->open(__DIR__ . '/inventory-link-remark-3.xlsx');
        $item = $reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertNull($item->getDocumentMetaData()->getLink());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());

        $reader->open(__DIR__ . '/inventory-link-remark-4.xlsx');
        $item = $reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://www.example.org', $item->getDocumentMetaData()->getLink());
        $this->assertEquals('https://notok.example.org', $item->getDocumentMetaData()->getRemark());

        $reader->open(__DIR__ . '/inventory-link-remark-5.xlsx');
        $item = $reader->getDocumentMetadataGenerator($dossier)->current();
        $this->assertEquals('https://example.org', $item->getDocumentMetaData()->getLink());
        $this->assertEquals('foo bar', $item->getDocumentMetaData()->getRemark());
    }

    public function testAreDefaultSubjectsSet(): void
    {
        $factory = new InventoryReaderFactory();
        $reader = $factory->create();

        $dossier = new Dossier();
        $dossier->setDefaultSubjects(['foo', 'bar']);

        $reader->open(__DIR__ . '/inventory-subjects-1.xlsx');

        $result = iterator_to_array($reader->getDocumentMetadataGenerator($dossier));
        $this->assertEquals(['subject 1', 'subject 2'], $result[0]->getDocumentMetadata()->getSubjects());
        $this->assertEquals(['foo', 'bar'], $result[1]->getDocumentMetadata()->getSubjects());
    }
}
