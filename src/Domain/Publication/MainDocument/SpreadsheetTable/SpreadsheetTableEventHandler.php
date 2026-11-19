<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Service\Storage\EntityStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function pathinfo;
use function strtolower;

use const PATHINFO_EXTENSION;

final readonly class SpreadsheetTableEventHandler
{
    public function __construct(
        private MainDocumentRepository $mainDocumentRepository,
        private EntityStorageService $entityStorageService,
        private SpreadsheetTableFactory $spreadsheetTableFactory,
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler]
    public function handleCreate(MainDocumentCreatedEvent $event): void
    {
        $this->generate($event->documentId);
    }

    #[AsMessageHandler]
    public function handleUpdate(MainDocumentUpdatedEvent $event): void
    {
        if (! $event->fileUpdated) {
            return;
        }

        $this->generate($event->documentId);
    }

    private function generate(Uuid $documentId): void
    {
        $mainDocument = $this->mainDocumentRepository->find($documentId);
        if (! $mainDocument instanceof EntityWithSpreadsheetTable) {
            return;
        }

        $spreadsheetTableResult = $this->createSpreadsheetTableResult($mainDocument);

        $mainDocument->setSpreadsheetTable($spreadsheetTableResult?->table);
        $mainDocument->setSpreadsheetTableTruncated($spreadsheetTableResult->truncated ?? false);

        $this->mainDocumentRepository->save($mainDocument, true);
    }

    private function createSpreadsheetTableResult(AbstractMainDocument&EntityWithSpreadsheetTable $mainDocument): ?SpreadsheetTableResult
    {
        $fileName = $mainDocument->getFileInfo()->getName();
        Assert::string($fileName);

        $fileExtension = FileExtension::tryFrom(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));
        if ($fileExtension === null || ! $this->spreadsheetTableFactory->supports($fileExtension)) {
            return null;
        }

        $localPath = $this->entityStorageService->downloadEntity($mainDocument);
        if ($localPath === false) {
            $this->logger->error('Could not download main document for spreadsheet table', [
                'id' => $mainDocument->getId()->toRfc4122(),
            ]);

            return null;
        }

        try {
            return $this->spreadsheetTableFactory->create($localPath, $fileExtension);
        } catch (SpreadsheetTableException $spreadsheetTableException) {
            $this->logger->warning('Could not create spreadsheet table for main document', [
                'id' => $mainDocument->getId()->toRfc4122(),
                'exception' => $spreadsheetTableException->getMessage(),
            ]);

            return null;
        } finally {
            $this->entityStorageService->removeDownload($localPath);
        }
    }
}
