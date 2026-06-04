<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Domain\Publication\Dossier\Event\DossierNrChangedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DossierNrChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    public function onDossierNrChanged(DossierNrChangedEvent $event): void
    {
        if (! $event->status->isPubliclyAvailableOrScheduled()) {
            return;
        }

        $this->historyService->addDossierEntry(
            $event->dossierId,
            'dossier_update_dossier_nr',
            [
                'oldNr' => $event->oldDossierNr,
                'newNr' => $event->newDossierNr,
            ],
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DossierNrChangedEvent::class => 'onDossierNrChanged',
        ];
    }
}
