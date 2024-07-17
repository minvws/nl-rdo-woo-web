<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\EntityWithFileInfo;
use App\Entity\FileInfo;
use App\Exception\InventorySanitizerException;
use App\Service\Storage\EntityStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventorySanitizer
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly EntityStorageService $entityStorageService,
        private readonly TranslatorInterface $translator,
        private readonly InventoryWriterInterface $writer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $publicBaseUrl,
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
            'Locatie open.minvws.nl',
            'Opgeschort',
            'Definitief ID',
        );

        foreach ($dataProvider->getDocuments() as $document) {
            $this->writer->addRow(...$this->getCellValues($document));
        }

        $this->writer->close();

        $inventoryEntity = $dataProvider->getInventoryEntity();
        $this->persistInventory($inventoryEntity, $dataProvider->getFilename());
        if (! $this->entityStorageService->storeEntity(new \SplFileInfo($tmpFilename), $inventoryEntity)) {
            throw new InventorySanitizerException('Could not store the sanitized inventory spreadsheet.');
        }
    }

    /**
     * @return array<int, array<string>|string>
     */
    private function getCellValues(Document $document): array
    {
        /** @var Dossier $dossier */
        $dossier = $document->getDossiers()->first();

        return [
            $document->getDocumentId() ?: '',
            $document->getDocumentNr(),
            $document->getFileInfo()->getName() ?: '',
            $document->getJudgement() ? $this->translator->trans($document->getJudgement()->value) : '',
            $document->getGrounds(),
            $document->getRemark() ?: '',
            implode("\n", $document->getLinks()),
            $this->publicBaseUrl . $this->urlGenerator->generate(
                'app_document_detail',
                [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'documentId' => $document->getDocumentNr(),
                ],
            ),
            $document->isSuspended() ? 'ja' : '',
            '',
        ];
    }

    private function persistInventory(EntityWithFileInfo $inventoryEntity, string $filename): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $fileInfo->setType($this->writer->getFileExtension());
        $fileInfo->setName($filename . '.' . $this->writer->getFileExtension());
        $inventoryEntity->setFileInfo($fileInfo);

        $this->doctrine->persist($inventoryEntity);
    }
}
