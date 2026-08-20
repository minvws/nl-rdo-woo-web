<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History\Listener;

use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DossierNumberHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    public function __invoke(DossierNumberChangedEvent $event): void
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
}
