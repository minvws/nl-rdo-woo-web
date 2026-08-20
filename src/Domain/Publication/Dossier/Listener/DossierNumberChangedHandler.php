<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Listener;

use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DossierNumberChangedHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private DossierDispatcher $dossierDispatcher,
    ) {
    }

    public function __invoke(DossierNumberChangedEvent $event): void
    {
        $dossier = $this->dossierRepository->findOneByDossierId($event->dossierId);
        if (! $dossier instanceof WooDecision) {
            return;
        }

        $this->dossierDispatcher->dispatchSynchronizeArtifactsCommand($dossier);
    }
}
