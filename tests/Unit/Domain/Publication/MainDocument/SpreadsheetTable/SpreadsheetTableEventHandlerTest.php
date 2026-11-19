<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\SpreadsheetTable;

use Mockery;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableEventHandler;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableFactory;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableResult;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function sprintf;

final class SpreadsheetTableEventHandlerTest extends UnitTestCase
{
    public function testHandleCreate(): void
    {
        $fileName = sprintf('%s.xlsx', $this->getFaker()->word());
        $localPath = $this->getFaker()->filePath();
        $spreadsheetTable = new SpreadsheetTable([$this->getFaker()->word()], [[$this->getFaker()->word()]]);
        $truncated = $this->getFaker()->boolean();

        $mainDocument = new DispositionMainDocument(
            Mockery::mock(Disposition::class),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $mainDocument->getFileInfo()->setName($fileName);

        $mainDocumentRepository = Mockery::mock(MainDocumentRepository::class);
        $mainDocumentRepository->expects('find')
            ->with($mainDocument->getId())
            ->andReturn($mainDocument);
        $mainDocumentRepository->expects('save')->with($mainDocument, true);

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('downloadEntity')
            ->with($mainDocument)
            ->andReturn($localPath);
        $entityStorageService->expects('removeDownload')->with($localPath);

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::XLSX)
            ->andReturnTrue();
        $spreadsheetTableFactory->expects('create')
            ->with($localPath, FileExtension::XLSX)
            ->andReturn(new SpreadsheetTableResult($spreadsheetTable, $truncated));

        $spreadsheetTableEventHandler = new SpreadsheetTableEventHandler(
            $mainDocumentRepository,
            $entityStorageService,
            $spreadsheetTableFactory,
            Mockery::mock(LoggerInterface::class),
        );
        $spreadsheetTableEventHandler->handleCreate(
            new MainDocumentCreatedEvent($mainDocument->getId(), Uuid::v6(), $fileName),
        );

        self::assertEquals($spreadsheetTable, $mainDocument->getSpreadsheetTable());
        self::assertSame($truncated, $mainDocument->isSpreadsheetTableTruncated());
    }

    public function testHandleCreateResetsToNullWhenInvalid(): void
    {
        $fileName = sprintf('%s.xls', $this->getFaker()->word());
        $localPath = $this->getFaker()->filePath();

        $mainDocument = new DispositionMainDocument(
            Mockery::mock(Disposition::class),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $mainDocument->getFileInfo()->setName($fileName);
        $mainDocument->setSpreadsheetTable(new SpreadsheetTable([$this->getFaker()->word()], [[$this->getFaker()->word()]]));
        $mainDocument->setSpreadsheetTableTruncated(true);

        $mainDocumentRepository = Mockery::mock(MainDocumentRepository::class);
        $mainDocumentRepository->expects('find')
            ->with($mainDocument->getId())
            ->andReturn($mainDocument);
        $mainDocumentRepository->expects('save')->with($mainDocument, true);

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('downloadEntity')
            ->with($mainDocument)
            ->andReturn($localPath);
        $entityStorageService->expects('removeDownload')->with($localPath);

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::XLS)
            ->andReturnTrue();
        $spreadsheetTableFactory->expects('create')
            ->with($localPath, FileExtension::XLS)
            ->andThrow(SpreadsheetTableException::forMultipleWorksheets());

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('warning');

        $spreadsheetTableEventHandler = new SpreadsheetTableEventHandler(
            $mainDocumentRepository,
            $entityStorageService,
            $spreadsheetTableFactory,
            $logger,
        );
        $spreadsheetTableEventHandler->handleCreate(
            new MainDocumentCreatedEvent($mainDocument->getId(), Uuid::v6(), $fileName),
        );

        self::assertNull($mainDocument->getSpreadsheetTable());
        self::assertFalse($mainDocument->isSpreadsheetTableTruncated());
    }
}
