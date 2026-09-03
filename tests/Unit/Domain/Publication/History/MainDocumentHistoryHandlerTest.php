<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\History\MainDocumentHistoryHandler;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class MainDocumentHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DossierRepository&MockInterface $repository;
    private MainDocumentHistoryHandler $handler;

    protected function setUp(): void
    {
        $this->historyService = Mockery::mock(HistoryService::class);
        $this->repository = Mockery::mock(DossierRepository::class);

        $this->handler = new MainDocumentHistoryHandler(
            $this->historyService,
            $this->repository,
        );

        parent::setUp();
    }

    public function testHandleCreate(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentCreatedEvent::forDocument($mainDocument);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'covenant.main_document_added',
                [
                    'filename' => $expectedName,
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleCreate($event);
    }

    public function testHandleUpdateWithoutFileUpload(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentUpdatedEvent::forDocument($mainDocument, fileUpdated: false, metadataUpdated: true);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'covenant.main_document_updated',
                [
                    'filename' => $expectedName,
                ],
                HistoryService::MODE_BOTH,
            );

        $this->handler->handleUpdate($event);
    }

    public function testHandleUpdateWithOnlyFileUploadLogsOnlyReplacedEntry(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentUpdatedEvent::forDocument($mainDocument, fileUpdated: true, metadataUpdated: false);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'covenant.main_document_replaced',
                [
                    'filename' => $expectedName,
                ],
                HistoryService::MODE_BOTH,
            );

        $this->handler->handleUpdate($event);
    }

    public function testHandleUpdateWithFileUploadAndMetadataChangeLogsTwoEntries(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED, getIdTimes: 6, times: 2);
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->expects('findOneByDossierId')->times(2)->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentUpdatedEvent::forDocument($mainDocument, fileUpdated: true, metadataUpdated: true);

        $this->historyService
            ->expects('addDossierEntry')
            ->with($dossier->getId(), 'covenant.main_document_updated', ['filename' => $expectedName], HistoryService::MODE_BOTH)
            ->ordered();

        $this->historyService
            ->expects('addDossierEntry')
            ->with($dossier->getId(), 'covenant.main_document_replaced', ['filename' => $expectedName], HistoryService::MODE_BOTH)
            ->ordered();

        $this->handler->handleUpdate($event);
    }

    public function testHandleUpdateWithoutAnyChangeLogsNothing(): void
    {
        $fileInfo = $this->getFileInfo('my-file-name');

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $event = MainDocumentUpdatedEvent::forDocument($mainDocument, fileUpdated: false, metadataUpdated: false);

        $this->repository->expects('findOneByDossierId')->never();
        $this->historyService->expects('addDossierEntry')->never();

        $this->handler->handleUpdate($event);
    }

    public function testHandleDelete(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentDeletedEvent::forDocument($mainDocument);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'covenant.main_document_deleted',
                [
                    'filename' => $expectedName,
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleDelete($event);
    }

    private function getDossier(DossierStatus $status, int $getIdTimes = 4, int $times = 1): Covenant
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times($getIdTimes)->andReturn(Uuid::v6());
        $dossier->expects('getType')->times($times)->andReturn(DossierType::COVENANT);
        $dossier->expects('getStatus')->times($times)->andReturn($status);

        return $dossier;
    }

    private function getFileInfo(string $name): FileInfo
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($name);

        return $fileInfo;
    }

    private function getMainDocument(FileInfo $fileInfo, Covenant $dossier): AbstractMainDocument
    {
        $document = Mockery::mock(AbstractMainDocument::class);
        $document->expects('getFileInfo')->andReturn($fileInfo);
        $document->expects('getId')->andReturn(Uuid::v6());
        $document->expects('getDossier')->andReturn($dossier);

        return $document;
    }
}
