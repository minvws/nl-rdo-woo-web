<?php

declare(strict_types=1);

namespace Shared\Domain\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postPersist, entity: AbstractAttachment::class, method: 'postPersist')]
#[AsEntityListener(event: Events::postUpdate, entity: AbstractAttachment::class, method: 'postUpdate')]
#[AsEntityListener(event: Events::postRemove, entity: AbstractAttachment::class, method: 'postRemove')]
readonly class AbstractAttachmentListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postPersist(AbstractAttachment $attachment): void
    {
        $this->dispatchEvent($attachment);
    }

    public function postUpdate(AbstractAttachment $attachment): void
    {
        $this->dispatchEvent($attachment);
    }

    public function postRemove(AbstractAttachment $attachment): void
    {
        $this->dispatchEvent($attachment);
    }

    private function dispatchEvent(AbstractAttachment $attachment): void
    {
        $dossier = $attachment->getDossier();

        $this->eventDispatcher->dispatch(new DossierChangedEvent($dossier));
    }
}
