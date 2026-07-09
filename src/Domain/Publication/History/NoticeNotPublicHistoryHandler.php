<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\AbstractNoticeNotPublicEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicCreatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicDeletedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicUpdatedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

final readonly class NoticeNotPublicHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $repository,
    ) {
    }

    #[AsMessageHandler]
    public function handleCreate(NoticeNotPublicCreatedEvent $event): void
    {
        $this->logEventToHistory($event, 'notice_not_public_added');
    }

    #[AsMessageHandler]
    public function handleUpdate(NoticeNotPublicUpdatedEvent $event): void
    {
        $this->logEventToHistory($event, 'notice_not_public_updated');
    }

    #[AsMessageHandler]
    public function handleDelete(NoticeNotPublicDeletedEvent $event): void
    {
        $this->logEventToHistory($event, 'notice_not_public_deleted');
    }

    private function logEventToHistory(AbstractNoticeNotPublicEvent $event, string $key): void
    {
        $dossier = $this->repository->findOneByDossierId($event->dossierId);

        $this->historyService->addDossierEntry($dossier->getId(), sprintf('%s.%s', $dossier->getType()->value, $key));
    }
}
