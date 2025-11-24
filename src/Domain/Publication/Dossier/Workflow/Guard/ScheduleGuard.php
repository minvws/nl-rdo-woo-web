<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow\Guard;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

final class ScheduleGuard
{
    #[AsEventListener(event: 'workflow.guard')]
    public function guardSchedule(GuardEvent $event): void
    {
        if ($event->getTransition()->getName() !== DossierStatusTransition::SCHEDULE_PUBLISH->value) {
            return;
        }

        /** @var AbstractDossier $dossier */
        $dossier = $event->getSubject();

        if (! $dossier->isCompleted()) {
            $event->setBlocked(true, 'Dossier publication cannot be scheduled for an incomplete dossier');
        }
    }
}
