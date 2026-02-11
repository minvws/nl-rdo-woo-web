<?php

declare(strict_types=1);

namespace Shared\Domain\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postPersist, entity: AbstractDossier::class, method: 'postPersist')]
#[AsEntityListener(event: Events::postUpdate, entity: AbstractDossier::class, method: 'postUpdate')]
readonly class AbstractDossierListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postPersist(AbstractDossier $dossier): void
    {
        $this->dispatchEvent($dossier);
    }

    public function postUpdate(AbstractDossier $dossier): void
    {
        $this->dispatchEvent($dossier);
    }

    private function dispatchEvent(AbstractDossier $dossier): void
    {
        $this->eventDispatcher->dispatch(new DossierChangedEvent($dossier));
    }
}
