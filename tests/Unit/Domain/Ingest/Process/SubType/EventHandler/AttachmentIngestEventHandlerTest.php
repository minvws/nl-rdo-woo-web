<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\EventHandler;

use App\Domain\Ingest\Process\SubType\EventHandler\AttachmentIngestEventHandler;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Search\SearchDispatcher;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class AttachmentIngestEventHandlerTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;
    private AttachmentCreatedEvent $event;

    protected function setUp(): void
    {
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);
        $this->event = new AttachmentCreatedEvent(
            dossierId: Uuid::v6(),
            attachmentId: Uuid::v6(),
            fileName: 'file-name',
            fileType: 'mime-type',
            fileSize: '123',
        );
    }

    public function testHandleCreate(): void
    {
        $this->searchDispatcher
            ->expects('dispatchIndexAttachmentCommand')
            ->with($this->event->attachmentId);

        $handler = new AttachmentIngestEventHandler($this->searchDispatcher);
        $handler->handleCreate($this->event);
    }
}
