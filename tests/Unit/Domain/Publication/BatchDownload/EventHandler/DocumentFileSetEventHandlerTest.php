<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\EventHandler;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\BatchDownload\EventHandler\DocumentFileSetEventHandler;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class DocumentFileSetEventHandlerTest extends MockeryTestCase
{
    private WooDecisionRepository&MockInterface $repository;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentFileSetEventHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(WooDecisionRepository::class);
        $this->batchDownloadService = \Mockery::mock(BatchDownloadService::class);

        $this->handler = new DocumentFileSetEventHandler(
            $this->repository,
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testHandleDocumentFileSetProcessedUpdatesBatchDownloadForPublicDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOne')->with($dossierId)->andReturn($dossier);

        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));

        $this->handler->handleDocumentFileSetProcessed($event);
    }

    public function testHandleDocumentFileSetProcessedSkipsNonPublicDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOne')->with($dossierId)->andReturn($dossier);

        $this->handler->handleDocumentFileSetProcessed($event);
    }
}
