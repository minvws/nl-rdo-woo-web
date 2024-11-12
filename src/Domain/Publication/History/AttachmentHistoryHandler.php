<?php

declare(strict_types=1);

namespace App\Domain\Publication\History;

use App\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class AttachmentHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $dossierRepository,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreate(AttachmentCreatedEvent $event): void
    {
        $dossier = $this->getDossier($event);

        $this->historyService->addDossierEntry(
            dossier: $dossier,
            key: 'attachment_created',
            context: $this->getContext($event),
            mode: $dossier->getStatus()->isPublished() ? HistoryService::MODE_BOTH : HistoryService::MODE_PRIVATE,
        );
    }

    #[AsMessageHandler()]
    public function handleUpdate(AttachmentUpdatedEvent $event): void
    {
        $dossier = $this->getDossier($event);

        $this->historyService->addDossierEntry(
            dossier: $dossier,
            key: 'attachment_updated',
            context: $this->getContext($event),
            mode: $dossier->getStatus()->isPublished() ? HistoryService::MODE_BOTH : HistoryService::MODE_PRIVATE,
        );
    }

    #[AsMessageHandler()]
    public function handleDelete(AttachmentDeletedEvent $event): void
    {
        $dossier = $this->getDossier($event);

        $this->historyService->addDossierEntry(
            dossier: $dossier,
            key: 'attachment_deleted',
            context: $this->getContext($event),
            mode: HistoryService::MODE_PRIVATE,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getContext(AbstractAttachmentEvent $event): array
    {
        return [
            'filename' => $event->fileName,
            'filetype' => $event->fileType,
            'filesize' => $event->fileSize,
        ];
    }

    private function getDossier(AbstractAttachmentEvent $event): AbstractDossier
    {
        return $this->dossierRepository->findOneByDossierId($event->dossierId);
    }
}
