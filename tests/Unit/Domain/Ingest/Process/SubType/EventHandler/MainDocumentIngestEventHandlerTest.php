<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\EventHandler;

use App\Domain\Ingest\Process\SubType\EventHandler\MainDocumentIngestEventHandler;
use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Search\SearchDispatcher;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class MainDocumentIngestEventHandlerTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;
    private MainDocumentCreatedEvent $event;

    protected function setUp(): void
    {
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);
        $this->event = new MainDocumentCreatedEvent(
            documentId: Uuid::v6(),
            dossierId: Uuid::v6(),
            filename: 'file-name',
        );
    }

    public function testHandleCreate(): void
    {
        $this->searchDispatcher
            ->expects('dispatchIndexMainDocumentCommand')
            ->with($this->event->documentId);

        $handler = new MainDocumentIngestEventHandler($this->searchDispatcher);
        $handler->handleCreate($this->event);
    }
}
