<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType\EventHandler;

use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MainDocumentIngestEventHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(MainDocumentCreatedEvent|MainDocumentUpdatedEvent $event): void
    {
        $this->messageBus->dispatch(
            IndexMainDocumentCommand::forMainDocumentEvent($event)
        );
    }
}
