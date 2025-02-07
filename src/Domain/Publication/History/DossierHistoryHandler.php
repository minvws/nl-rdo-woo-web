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
        $dossier = $this->repository->findOneByDossierId($event->id);

        $this->historyService->addDossierEntry(
            dossier: $dossier,
            key: 'dossier_created',
        );
    }
}
