<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Service\HistoryService;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class DossierHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $repository,
        private ApplicationMode $applicationMode,
    ) {
    }

    #[AsMessageHandler()]
    public function handleCreated(DossierCreatedEvent $event): void
    {
        $dossier = $this->repository->findOneByDossierId($event->dossierId);

        $this->historyService->addDossierEntry(
            dossierId: $event->dossierId,
            key: 'dossier_created',
            context: [
                'applicationMode' => $this->applicationMode->value,
                'status' => $dossier->getStatus()->value,
            ],
        );
    }
}
