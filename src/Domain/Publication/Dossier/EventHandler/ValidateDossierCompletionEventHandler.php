<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\EventHandler;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class ValidateDossierCompletionEventHandler
{
    public function __construct(
        private DossierRepository $repository,
        private DossierService $dossierService,
    ) {
    }

    #[AsMessageHandler]
    public function handleDocumentFileSetProcessedEvent(DocumentFileSetProcessedEvent $event): void
    {
        $dossier = $this->repository->findOneByDossierId($event->dossierId);

        $this->dossierService->validateCompletion($dossier);
    }
}
