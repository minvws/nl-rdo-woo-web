<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentAction;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentsCommand;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentsHandler;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class ProcessUploadedDocumentsHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private ProcessUploadedDocumentAction&MockInterface $processUploadedDocumentAction;
    private DocumentFileService&MockInterface $documentFileService;
    private ProcessUploadedDocumentsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $this->processUploadedDocumentAction = Mockery::mock(ProcessUploadedDocumentAction::class);
        $this->documentFileService = Mockery::mock(DocumentFileService::class);

        $this->handler = new ProcessUploadedDocumentsHandler(
            $this->wooDecisionRepository,
            $this->uploadEntityRepository,
            $this->processUploadedDocumentAction,
            $this->documentFileService,
        );
    }

    public function testReturnsWhenWooDecisionNotFound(): void
    {
        $wooDecisionId = Uuid::v6();
        $uploadEntityId = Uuid::v6();

        $this->wooDecisionRepository->expects('find')->with($wooDecisionId)->andReturnNull();

        $this->processUploadedDocumentAction->shouldNotReceive('execute');
        $this->documentFileService->shouldNotReceive('startSaveProcessingUploads');

        $command = new ProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntityId);

        ($this->handler)($command);
    }

    public function testReturnsWhenUploadEntityNotFound(): void
    {
        $wooDecisionId = Uuid::v6();
        $uploadEntityId = Uuid::v6();

        $wooDecision = Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository->expects('find')->with($wooDecisionId)->andReturn($wooDecision);
        $this->uploadEntityRepository->expects('find')->with($uploadEntityId)->andReturnNull();

        $this->processUploadedDocumentAction->shouldNotReceive('execute');
        $this->documentFileService->shouldNotReceive('startSaveProcessingUploads');

        $command = new ProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntityId);

        ($this->handler)($command);
    }

    public function testProcessesSuccessfully(): void
    {
        $wooDecisionId = Uuid::v6();
        $uploadEntityId = Uuid::v6();

        $wooDecision = Mockery::mock(WooDecision::class);
        $uploadEntity = Mockery::mock(UploadEntity::class);

        $this->wooDecisionRepository->expects('find')->with($wooDecisionId)->andReturn($wooDecision);
        $this->uploadEntityRepository->expects('find')->with($uploadEntityId)->andReturn($uploadEntity);

        $this->processUploadedDocumentAction->expects('execute')->with($uploadEntity)->andReturnNull();
        $this->documentFileService->expects('startSaveProcessingUploads')->with($wooDecision)->andReturnNull();

        $command = new ProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntityId);

        ($this->handler)($command);
    }
}
