<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierNrChangedEvent;
use Shared\EventSubscriber\DossierNrChangedSubscriber;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DossierNrChangedSubscriberTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [DossierNrChangedEvent::class => 'onDossierNrChanged'],
            DossierNrChangedSubscriber::getSubscribedEvents(),
        );
    }

    public function testHistoryWrittenForPubliclyAvailableOrScheduledStatus(): void
    {
        $dossierId = Uuid::v6();
        $event = new DossierNrChangedEvent($dossierId, 'old-nr', 'new-nr', DossierStatus::PUBLISHED);

        $historyService = Mockery::mock(HistoryService::class);
        $historyService->expects('addDossierEntry')->with(
            $dossierId,
            'dossier_update_dossier_nr',
            ['oldNr' => 'old-nr', 'newNr' => 'new-nr'],
        );

        $subscriber = new DossierNrChangedSubscriber($historyService);
        $subscriber->onDossierNrChanged($event);
    }

    public function testNoHistoryWrittenForConceptStatus(): void
    {
        $event = new DossierNrChangedEvent(Uuid::v6(), 'old-nr', 'new-nr', DossierStatus::CONCEPT);

        $historyService = Mockery::mock(HistoryService::class);

        $subscriber = new DossierNrChangedSubscriber($historyService);
        $subscriber->onDossierNrChanged($event);

        $historyService->shouldNotHaveReceived('addDossierEntry');
    }
}
