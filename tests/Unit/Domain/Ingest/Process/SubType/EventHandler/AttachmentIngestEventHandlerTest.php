<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\SubType\EventHandler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\SubType\EventHandler\AttachmentIngestEventHandler;
use Shared\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class AttachmentIngestEventHandlerTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;
    private AttachmentCreatedEvent $event;

    protected function setUp(): void
    {
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);
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
