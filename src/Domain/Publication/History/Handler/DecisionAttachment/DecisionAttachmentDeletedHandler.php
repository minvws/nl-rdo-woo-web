<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\Handler\DecisionAttachment;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentDeletedEvent;
use App\Service\HistoryService;
use App\Utils;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DecisionAttachmentDeletedHandler
{
    public function __construct(private HistoryService $historyService)
    {
    }

    public function __invoke(DecisionAttachmentDeletedEvent $event): void
    {
        $this->historyService->addDossierEntry(
            $event->dossier,
            key: 'decision_attachment_deleted',
            context: [
                'filename' => $event->decisionAttachment->getFileInfo()->getName(),
                'filetype' => $event->decisionAttachment->getFileInfo()->getType(),
                'filesize' => Utils::getFileSize($event->decisionAttachment),
            ],
            mode: HistoryService::MODE_PRIVATE,
        );
    }
}
