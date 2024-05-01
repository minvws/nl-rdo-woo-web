<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\Handler\DecisionAttachment;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentCreatedEvent;
use App\Service\HistoryService;
use App\Utils;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
final readonly class DecisionAttachmentCreatedHandler
{
    public function __construct(private HistoryService $historyService)
    {
    }

    public function __invoke(DecisionAttachmentCreatedEvent $event): void
    {
        $this->historyService->addDossierEntry(
            $event->decisionAttachment->getDossier(),
            key: 'decision_attachment_created',
            context: [
                'filename' => $event->decisionAttachment->getFileInfo()->getName(),
                'filetype' => $event->decisionAttachment->getFileInfo()->getType(),
                'filesize' => Utils::getFileSize($event->decisionAttachment),
            ],
            mode: $event->decisionAttachment->getDossier()->getStatus() === DossierStatus::PUBLISHED
                ? HistoryService::MODE_BOTH
                : HistoryService::MODE_PRIVATE,
        );
    }
}
