<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\EventSubscriber\DossierNumberChangedSubscriber;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DossierNumberChangedSubscriberTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [DossierNumberChangedEvent::class => 'onDossierNumberChanged'],
            DossierNumberChangedSubscriber::getSubscribedEvents(),
        );
    }

    public function testHistoryWrittenForPubliclyAvailableOrScheduledStatus(): void
    {
        $dossierId = Uuid::v6();
        $event = new DossierNumberChangedEvent($dossierId, 'old-nr', 'new-nr', DossierStatus::PUBLISHED);

        $historyService = Mockery::mock(HistoryService::class);
        $historyService->expects('addDossierEntry')->with(
            $dossierId,
            'dossier_update_dossier_number',
            [
                'oldNr' => 'old-nr',
                'newNr' => 'new-nr',
            ],
        );

        $subscriber = new DossierNumberChangedSubscriber($historyService);
        $subscriber->onDossierNumberChanged($event);
    }

    public function testNoHistoryWrittenForConceptStatus(): void
    {
        $event = new DossierNumberChangedEvent(Uuid::v6(), 'old-nr', 'new-nr', DossierStatus::CONCEPT);

        $historyService = Mockery::mock(HistoryService::class);

        $subscriber = new DossierNumberChangedSubscriber($historyService);
        $subscriber->onDossierNumberChanged($event);

        $historyService->shouldNotHaveReceived('addDossierEntry');
    }
}
