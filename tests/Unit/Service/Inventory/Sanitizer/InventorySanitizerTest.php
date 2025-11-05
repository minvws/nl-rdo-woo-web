<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory\Sanitizer;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\FileInfo;
use App\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderInterface;
use App\Service\Inventory\Sanitizer\InventoryDocumentMapper;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use App\Service\Inventory\Sanitizer\InventoryWriterInterface;
use App\Service\Storage\EntityStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InventorySanitizerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private EntityStorageService&MockInterface $entityStorageService;
    private InventoryWriterInterface&MockInterface $writer;
    private InventoryDocumentMapper&MockInterface $documentMapper;
    private InventorySanitizer $sanitizer;
    private InventoryDataProviderInterface&MockInterface $dataProvider;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->writer = \Mockery::mock(InventoryWriterInterface::class);

        $this->dataProvider = \Mockery::mock(InventoryDataProviderInterface::class);

        $this->documentMapper = \Mockery::mock(InventoryDocumentMapper::class);

        $this->sanitizer = new InventorySanitizer(
            $this->entityManager,
            $this->entityStorageService,
            $this->writer,
            $this->documentMapper,
        );

        parent::setUp();
    }

    public function testFileIsWrittenAndInventoryPersisted(): void
    {
        $document = \Mockery::mock(Document::class);
        $this->documentMapper
            ->expects('map')
            ->with($document)
            ->andReturn($documentData = ['foo', 'bar']);

        $this->writer->expects('open');
        $this->writer->expects('addHeaders');
        $this->writer->expects('addRow')->with(...$documentData);
        $this->writer->expects('close');
        $this->writer->expects('getFileExtension')->twice()->andReturn('csv');

        $inventory = \Mockery::mock(Inventory::class);
        $inventory->expects('setFileInfo')->with(\Mockery::on(
            static function (FileInfo $fileInfo) {
                self::assertEquals('foo-bar.csv', $fileInfo->getName());

                return true;
            }
        ));

        $this->dataProvider->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));
        $this->dataProvider->shouldReceive('getInventoryEntity')->andReturn($inventory);
        $this->dataProvider->shouldReceive('getFilename')->andReturn('foo-bar');
        $this->entityManager->expects('persist')->with($inventory);

        $this->entityStorageService->expects('storeEntity')->andReturnTrue();

        $this->sanitizer->generateSanitizedInventory($this->dataProvider);
    }
}
