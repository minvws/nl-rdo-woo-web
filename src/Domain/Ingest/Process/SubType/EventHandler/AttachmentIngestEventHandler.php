<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\SubType\EventHandler;

use Shared\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class AttachmentIngestEventHandler
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(AttachmentCreatedEvent|AttachmentUpdatedEvent $event): void
    {
        $this->searchDispatcher->dispatchIndexAttachmentCommand($event->attachmentId);
    }
}
