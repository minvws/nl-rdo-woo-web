<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Domain\Event\DossierChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class DossierChangedCollectionBuilder implements EventSubscriberInterface
{
    public function __construct(
        private DossierChangedCollection $dossierChangedCollection,
    ) {
    }

    public function onDossierChanged(DossierChangedEvent $event): void
    {
        $this->dossierChangedCollection->addDossierId($event->getDossier()->getId());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DossierChangedEvent::class => 'onDossierChanged',
        ];
    }
}
