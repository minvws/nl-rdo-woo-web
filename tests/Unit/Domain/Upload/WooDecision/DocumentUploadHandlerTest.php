<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\WooDecision\DocumentUploadHandler;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentAction;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;

class DocumentUploadHandlerTest extends UnitTestCase
{
    private ProcessUploadedDocumentAction&MockInterface $processUploadedDocumentAction;
    private DocumentUploadHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processUploadedDocumentAction = Mockery::mock(ProcessUploadedDocumentAction::class);

        $this->handler = new DocumentUploadHandler($this->processUploadedDocumentAction);
    }

    public function testSkipsUploadsForOtherGroup(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }

    public function testHandleUploadSuccessfully(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->processUploadedDocumentAction->expects('execute')->with($uploadEntity)->andReturnNull();

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }
}
