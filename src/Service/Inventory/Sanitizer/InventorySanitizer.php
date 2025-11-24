<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\InventorySanitizerException;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderInterface;
use Shared\Service\Storage\EntityStorageService;

readonly class InventorySanitizer
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private InventoryWriterInterface $writer,
        private InventoryDocumentMapper $documentMapper,
    ) {
    }

    public function generateSanitizedInventory(InventoryDataProviderInterface $dataProvider): void
    {
        $tmpFilename = tempnam(sys_get_temp_dir(), 'inventory');
        if (! $tmpFilename) {
            throw new InventorySanitizerException('Could not create temporary file for sanitized inventory.');
        }

        $this->writer->open($tmpFilename);
        $this->writer->addHeaders(
            'Document ID',
            'Document naam',
            'Bestandsnaam',
            'Beoordeling',
            'Beoordelingsgrond',
            'Toelichting',
            'Publieke link',
            'Locatie document ID',
            'Opgeschort',
            'Gerelateerd ID',
            'Locatie gerelateerd ID',
            'Besluitnaam',
        );

        foreach ($dataProvider->getDocuments() as $document) {
            $this->writer->addRow(...$this->documentMapper->map($document));
        }

        $this->writer->close();

        $inventoryEntity = $dataProvider->getInventoryEntity();
        $this->persistInventory($inventoryEntity, $dataProvider->getFilename());
        if (! $this->entityStorageService->storeEntity(new \SplFileInfo($tmpFilename), $inventoryEntity)) {
            throw new InventorySanitizerException('Could not store the sanitized inventory spreadsheet.');
        }
    }

    private function persistInventory(EntityWithFileInfo $inventoryEntity, string $filename): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setSourceType(SourceType::SPREADSHEET);
        $fileInfo->setType($this->writer->getFileExtension());
        $fileInfo->setName($filename . '.' . $this->writer->getFileExtension());
        $inventoryEntity->setFileInfo($fileInfo);

        $this->doctrine->persist($inventoryEntity);
    }
}
