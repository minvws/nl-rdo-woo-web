<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\Handler\Document;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class AllDocumentsWithDrawnHandler
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    public function __invoke(AllDocumentsWithDrawnEvent $event): void
    {
        $this->historyService->addDossierEntry($event->dossier, 'dossier_withdraw_all', [
            'reason' => '%' . $event->reason->value . '%',
            'explanation' => $event->explanation,
        ]);
    }
}
