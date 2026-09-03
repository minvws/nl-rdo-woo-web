<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\SubType\EventHandler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\SubType\EventHandler\AttachmentIngestEventHandler;
use Shared\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\FileInfo;
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

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn('file-name');
        $fileInfo->expects('getType')->andReturn('mime-type');
        $fileInfo->expects('getSize')->andReturn(123);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $this->event = AttachmentCreatedEvent::forAttachment($attachment);
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
