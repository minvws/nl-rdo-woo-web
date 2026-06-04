<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Event\DossierNrChangedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: AbstractDossier::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: AbstractDossier::class)]
final class DossierNrChangedListener
{
    private ?DossierNrChangedEvent $pendingEvent = null;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function preUpdate(AbstractDossier $dossier, PreUpdateEventArgs $args): void
    {
        if (! $args->hasChangedField('dossierNr')) {
            return;
        }

        $oldValue = $args->getOldValue('dossierNr');
        if ($oldValue === null || $oldValue === '') {
            return;
        }

        $newValue = $args->getNewValue('dossierNr');
        Assert::string($oldValue);
        Assert::string($newValue);

        $this->pendingEvent = new DossierNrChangedEvent(
            $dossier->getId(),
            $oldValue,
            $newValue,
            $dossier->getStatus(),
        );
    }

    public function postUpdate(AbstractDossier $dossier, PostUpdateEventArgs $event): void
    {
        if ($this->pendingEvent === null) {
            return;
        }

        $this->dispatcher->dispatch($this->pendingEvent);
        $this->pendingEvent = null;
    }
}
