<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Listener;

use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Event\DossierTitleChangedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DossierTitleChangedHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private DossierDispatcher $dossierDispatcher,
    ) {
    }

    public function __invoke(DossierTitleChangedEvent $event): void
    {
        $dossier = $this->dossierRepository->findOneByDossierId($event->dossierId);

        $this->dossierDispatcher->dispatchSynchronizeArtifactsCommand($dossier);
    }
}
