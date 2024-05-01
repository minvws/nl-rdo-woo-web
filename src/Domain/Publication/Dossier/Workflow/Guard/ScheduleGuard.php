<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class ScheduleGuard implements EventSubscriberInterface
{
    public function guardSchedule(GuardEvent $event): void
    {
        if ($event->getTransition()->getName() !== DossierStatusTransition::SCHEDULE->value) {
            return;
        }

        /** @var AbstractDossier $dossier */
        $dossier = $event->getSubject();

        if (! $dossier->isCompleted()) {
            $event->setBlocked(true, 'Dossier publication cannot be scheduled for an incomplete dossier');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.guard' => ['guardSchedule'],
        ];
    }
}
