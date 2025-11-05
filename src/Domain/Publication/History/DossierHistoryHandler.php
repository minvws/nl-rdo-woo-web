<?php

declare(strict_types=1);

namespace App\Domain\Publication\History;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use App\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class DossierHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $repository,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreated(DossierCreatedEvent $event): void
    {
        $this->repository->findOneByDossierId($event->dossierId);

        $this->historyService->addDossierEntry(
            dossierId: $event->dossierId,
            key: 'dossier_created',
        );
    }
}
