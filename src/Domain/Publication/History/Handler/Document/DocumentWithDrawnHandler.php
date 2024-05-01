<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\Handler\Document;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DocumentWithDrawnHandler
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    public function __invoke(DocumentWithDrawnEvent $event): void
    {
        $this->historyService->addDocumentEntry($event->document, 'document_withdraw', [
            'explanation' => '%' . $event->reason->value . '%',
            'explanation_details' => $event->explanation,
        ]);
    }
}
