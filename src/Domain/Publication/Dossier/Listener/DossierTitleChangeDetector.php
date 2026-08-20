<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Event\DossierTitleChangedEvent;
use Shared\ValueObject\DossierTitle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: AbstractDossier::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: AbstractDossier::class)]
final class DossierTitleChangeDetector
{
    private ?DossierTitleChangedEvent $pendingEvent = null;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function preUpdate(AbstractDossier $dossier, PreUpdateEventArgs $args): void
    {
        if (! $args->hasChangedField('title')) {
            return;
        }

        $oldValue = $args->getOldValue('title');
        Assert::isInstanceOf($oldValue, DossierTitle::class);

        $newValue = $args->getNewValue('title');
        Assert::isInstanceOf($newValue, DossierTitle::class);

        $this->pendingEvent = new DossierTitleChangedEvent(
            $dossier->getId(),
            $oldValue,
            $newValue,
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
