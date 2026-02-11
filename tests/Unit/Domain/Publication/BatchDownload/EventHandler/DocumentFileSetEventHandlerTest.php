<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\EventHandler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\BatchDownload\EventHandler\DocumentFileSetEventHandler;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DocumentFileSetEventHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $repository;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentFileSetEventHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(WooDecisionRepository::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);

        $this->handler = new DocumentFileSetEventHandler(
            $this->repository,
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testHandleDocumentFileSetProcessedUpdatesBatchDownloadForPublicDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOne')->with($dossierId)->andReturn($dossier);

        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));

        $this->handler->handleDocumentFileSetProcessed($event);
    }

    public function testHandleDocumentFileSetProcessedSkipsNonPublicDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOne')->with($dossierId)->andReturn($dossier);

        $this->handler->handleDocumentFileSetProcessed($event);
    }
}
