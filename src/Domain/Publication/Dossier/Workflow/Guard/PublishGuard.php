<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow\Guard;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

final class PublishGuard
{
    #[AsEventListener(event: 'workflow.guard')]
    public function guardPublication(GuardEvent $event): void
    {
        if ($event->getTransition()->getName() !== DossierStatusTransition::PUBLISH->value) {
            return;
        }

        /** @var AbstractDossier $dossier */
        $dossier = $event->getSubject();
        if ($dossier->getPublicationDate() === null || $dossier->getPublicationDate() > new \DateTimeImmutable('now midnight')) {
            $event->setBlocked(true, 'A dossier with an empty or future publication date cannot be published');
        }
    }
}
