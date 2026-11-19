<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Domain\Event\DossierChangedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class DossierChangedCollectionBuilder
{
    public function __construct(
        private DossierChangedCollection $dossierChangedCollection,
    ) {
    }

    #[AsEventListener(event: DossierChangedEvent::class)]
    public function onDossierChanged(DossierChangedEvent $event): void
    {
        $this->dossierChangedCollection->addDossierId($event->getDossier()->getId());
    }
}
