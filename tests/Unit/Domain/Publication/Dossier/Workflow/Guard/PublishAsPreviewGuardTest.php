<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow\Guard;

use DateTimeImmutable;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\Guard\PublishAsPreviewGuard;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class PublishAsPreviewGuardTest extends UnitTestCase
{
    private PublishAsPreviewGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new PublishAsPreviewGuard();
    }

    public function testGuardPublicationAsPreviewAllowsOtherTransitions(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::UPDATE_DOCUMENTS->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertFalse($event->isBlocked());
    }

    public function testGuardPublicationAsPreviewBlocksDossierTypeWithoutPreview(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationAsPreviewBlocksDossierWithoutPreviewDate(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->andReturnNull();

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationAsPreviewBlocksDossierWithFuturePreviewDate(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->twice()->andReturn(new DateTimeImmutable('+1 year'));

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationAsPreviewAllowsValidDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->twice()->andReturn(new DateTimeImmutable('-1 day'));

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertFalse($event->isBlocked());
    }
}
