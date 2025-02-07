<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\Guard\ScheduleGuard;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class ScheduleGuardTest extends MockeryTestCase
{
    private ScheduleGuard $guard;

    public function setUp(): void
    {
        $this->guard = new ScheduleGuard();
    }

    public function testGuardScheduleAllowsOtherTransitions(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::UPDATE_DOCUMENTS->value, [], []),
        );

        $this->guard->guardSchedule($event);

        self::assertFalse($event->isBlocked());
    }

    public function testGuardScheduleBlocksIncompleteDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('isCompleted')->andReturnFalse();

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::SCHEDULE_PUBLISH->value, [], []),
        );

        $this->guard->guardSchedule($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardScheduleAllowsValidDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('isCompleted')->andReturnTrue();

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::SCHEDULE_PUBLISH->value, [], []),
        );

        $this->guard->guardSchedule($event);

        self::assertFalse($event->isBlocked());
    }
}
