<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\MainDocument\Event\AbstractMainDocumentEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

final readonly class MainDocumentHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $repository,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(MainDocumentCreatedEvent $event): void
    {
        $this->logEventToHistory($event, 'main_document_added', HistoryService::MODE_PRIVATE);
    }

    #[AsMessageHandler()]
    public function handleUpdate(MainDocumentUpdatedEvent $event): void
    {
        $this->logEventToHistory($event, 'main_document_updated', HistoryService::MODE_BOTH);
    }

    #[AsMessageHandler()]
    public function handleDelete(MainDocumentDeletedEvent $event): void
    {
        $this->logEventToHistory($event, 'main_document_deleted', HistoryService::MODE_PRIVATE);
    }

    private function logEventToHistory(AbstractMainDocumentEvent $event, string $key, string $mode): void
    {
        $dossier = $this->repository->findOneByDossierId($event->dossierId);

        $this->historyService->addDossierEntry(
            dossierId: $dossier->getId(),
            key: sprintf('%s.%s', $dossier->getType()->value, $key),
            context: [
                'filename' => $event->filename,
            ],
            mode: $mode,
        );
    }
}
