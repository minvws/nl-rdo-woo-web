<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\SubType\EventHandler;

use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class MainDocumentIngestEventHandler
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(MainDocumentCreatedEvent|MainDocumentUpdatedEvent $event): void
    {
        $this->searchDispatcher->dispatchIndexMainDocumentCommand($event->documentId);
    }
}
