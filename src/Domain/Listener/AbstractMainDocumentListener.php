<?php

declare(strict_types=1);

namespace Shared\Domain\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postPersist, entity: AbstractMainDocument::class, method: 'postPersist')]
#[AsEntityListener(event: Events::postUpdate, entity: AbstractMainDocument::class, method: 'postUpdate')]
#[AsEntityListener(event: Events::postRemove, entity: AbstractMainDocument::class, method: 'postRemove')]
readonly class AbstractMainDocumentListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postPersist(AbstractMainDocument $mainDocument): void
    {
        $this->dispatchEvent($mainDocument);
    }

    public function postUpdate(AbstractMainDocument $mainDocument): void
    {
        $this->dispatchEvent($mainDocument);
    }

    public function postRemove(AbstractMainDocument $mainDocument): void
    {
        $this->dispatchEvent($mainDocument);
    }

    private function dispatchEvent(AbstractMainDocument $mainDocument): void
    {
        $dossier = $mainDocument->getDossier();

        $this->eventDispatcher->dispatch(new DossierChangedEvent($dossier));
    }
}
