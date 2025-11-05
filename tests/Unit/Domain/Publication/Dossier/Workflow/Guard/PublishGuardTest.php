<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\Guard\PublishGuard;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class PublishGuardTest extends MockeryTestCase
{
    private PublishGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new PublishGuard();
    }

    public function testGuardPublicationAllowsOtherTransitions(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::UPDATE_DOCUMENTS->value, [], []),
        );

        $this->guard->guardPublication($event);

        self::assertFalse($event->isBlocked());
    }

    public function testGuardPublicationBlocksDossierWithoutPublicationDate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPublicationDate')->andReturnNull();

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH->value, [], []),
        );

        $this->guard->guardPublication($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationBlocksDossierWithFuturePublicationDate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPublicationDate')->twice()->andReturn(new \DateTimeImmutable('+1 year'));

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH->value, [], []),
        );

        $this->guard->guardPublication($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationAllowsValidDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPublicationDate')->twice()->andReturn(new \DateTimeImmutable('-1 day'));

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH->value, [], []),
        );

        $this->guard->guardPublication($event);

        self::assertFalse($event->isBlocked());
    }
}
