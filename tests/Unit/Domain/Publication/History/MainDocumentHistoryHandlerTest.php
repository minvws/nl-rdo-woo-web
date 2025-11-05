<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\FileInfo;
use App\Domain\Publication\History\MainDocumentHistoryHandler;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use App\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class MainDocumentHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DossierRepository&MockInterface $repository;
    private MainDocumentHistoryHandler $handler;

    protected function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->repository = \Mockery::mock(DossierRepository::class);

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
        $dossier = $this->getDossier();
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleCreate($event);
    }

    public function testHandleUpdate(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier();
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = MainDocumentUpdatedEvent::forDocument($mainDocument);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'covenant.main_document_updated',
                [
                    'filename' => $expectedName,
                ],
                HistoryService::MODE_BOTH,
            )
            ->once();

        $this->handler->handleUpdate($event);
    }

    public function testHandleDelete(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
        );
        $dossier = $this->getDossier();
        $mainDocument = $this->getMainDocument($fileInfo, $dossier);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleDelete($event);
    }

    private function getDossier(): Covenant
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        return $dossier;
    }

    private function getFileInfo(string $name): FileInfo
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($name);

        return $fileInfo;
    }

    private function getMainDocument(FileInfo $fileInfo, Covenant $dossier): AbstractMainDocument
    {
        $document = \Mockery::mock(AbstractMainDocument::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getId')->andReturn(Uuid::v6());
        $document->shouldReceive('getDossier')->andReturn($dossier);

        return $document;
    }
}
