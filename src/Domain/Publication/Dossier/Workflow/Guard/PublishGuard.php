<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class PublishGuard implements EventSubscriberInterface
{
    public function guardPublication(GuardEvent $event): void
    {
        if ($event->getTransition()->getName() !== DossierStatusTransition::PUBLISH->value) {
            return;
        }

        /** @var AbstractDossier $dossier */
        $dossier = $event->getSubject();
        if ($dossier->getPublicationDate() === null || $dossier->getPublicationDate() > new \DateTimeImmutable('now midnight')) {
            $event->setBlocked(true, 'A dossier with an empty or future publication date cannot be published');

            return;
        }

        if (! $dossier->isCompleted()) {
            $event->setBlocked(true, 'A dossier that is not completed cannot be published');
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.guard' => ['guardPublication'],
        ];
    }
}
