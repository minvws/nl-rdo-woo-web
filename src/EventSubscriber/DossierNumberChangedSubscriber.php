<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DossierNumberChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    public function onDossierNumberChanged(DossierNumberChangedEvent $event): void
    {
        if (! $event->status->isPubliclyAvailableOrScheduled()) {
            return;
        }

        $this->historyService->addDossierEntry(
            $event->dossierId,
            'dossier_update_dossier_number',
            [
                'oldNr' => $event->oldDossierNumber,
                'newNr' => $event->newDossierNumber,
            ],
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DossierNumberChangedEvent::class => 'onDossierNumberChanged',
        ];
    }
}
