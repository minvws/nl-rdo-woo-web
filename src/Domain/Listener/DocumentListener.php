<?php

declare(strict_types=1);

namespace Shared\Domain\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postPersist, entity: Document::class, method: 'postPersist')]
#[AsEntityListener(event: Events::postUpdate, entity: Document::class, method: 'postUpdate')]
#[AsEntityListener(event: Events::postRemove, entity: Document::class, method: 'postRemove')]
readonly class DocumentListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postPersist(Document $document): void
    {
        $this->dispatchEvents($document);
    }

    public function postUpdate(Document $document): void
    {
        $this->dispatchEvents($document);
    }

    public function postRemove(Document $document): void
    {
        $this->dispatchEvents($document);
    }

    private function dispatchEvents(Document $document): void
    {
        foreach ($document->getDossiers() as $dossier) {
            $this->eventDispatcher->dispatch(new DossierChangedEvent($dossier));
        }
    }
}
