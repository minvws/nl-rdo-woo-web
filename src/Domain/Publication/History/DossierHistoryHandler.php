<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class DossierHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DossierRepository $repository,
        private ApplicationId $applicationId,
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
                'applicationId' => $this->applicationId->value,
                'status' => $dossier->getStatus()->value,
            ],
        );
    }
}
