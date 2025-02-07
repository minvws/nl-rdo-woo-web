<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType\EventHandler;

use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use App\Domain\Search\SearchDispatcher;
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
