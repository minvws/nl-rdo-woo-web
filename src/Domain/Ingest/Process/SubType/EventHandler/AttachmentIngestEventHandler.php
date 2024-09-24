<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType\EventHandler;

use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AttachmentIngestEventHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(AttachmentCreatedEvent|AttachmentUpdatedEvent $event): void
    {
        $this->messageBus->dispatch(
            IndexAttachmentCommand::forAttachmentEvent($event)
        );
    }
}
