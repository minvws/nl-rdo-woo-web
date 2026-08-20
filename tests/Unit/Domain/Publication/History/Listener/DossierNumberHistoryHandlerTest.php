<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History\Listener;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Domain\Publication\History\Listener\DossierNumberHistoryHandler;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DossierNumberHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;

    private DossierNumberHistoryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->historyService = Mockery::mock(HistoryService::class);

        $this->handler = new DossierNumberHistoryHandler($this->historyService);
    }

    public function testHistoryWrittenForPubliclyAvailableOrScheduledStatus(): void
    {
        $dossierId = Uuid::v6();
        $event = new DossierNumberChangedEvent($dossierId, 'old-nr', 'new-nr', DossierStatus::PUBLISHED);

        $this->historyService->expects('addDossierEntry')->with(
            $dossierId,
            'dossier_update_dossier_number',
            [
                'oldNr' => 'old-nr',
                'newNr' => 'new-nr',
            ],
        );

        $this->handler->__invoke($event);
    }

    public function testNoHistoryWrittenForConceptStatus(): void
    {
        $event = new DossierNumberChangedEvent(Uuid::v6(), 'old-nr', 'new-nr', DossierStatus::CONCEPT);

        $this->handler->__invoke($event);

        $this->historyService->shouldNotHaveReceived('addDossierEntry');
    }
}
