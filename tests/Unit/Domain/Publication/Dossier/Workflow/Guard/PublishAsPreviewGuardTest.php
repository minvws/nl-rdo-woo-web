<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\Guard\PublishAsPreviewGuard;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class PublishAsPreviewGuardTest extends MockeryTestCase
{
    private PublishAsPreviewGuard $guard;

    public function setUp(): void
    {
        $this->guard = new PublishAsPreviewGuard();
    }

    public function testGuardPublicationAsPreviewAllowsOtherTransitions(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
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
        $dossier = \Mockery::mock(Covenant::class);
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
        $dossier = \Mockery::mock(WooDecision::class);
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
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->twice()->andReturn(new \DateTimeImmutable('+1 year'));

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertTrue($event->isBlocked());
    }

    public function testGuardPublicationAsPreviewBlocksIncompleteDossier(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->twice()->andReturn(new \DateTimeImmutable('-1 day'));
        $dossier->expects('isCompleted')->andReturnFalse();

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
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getPreviewDate')->twice()->andReturn(new \DateTimeImmutable('-1 day'));
        $dossier->expects('isCompleted')->andReturnTrue();

        $event = new GuardEvent(
            $dossier,
            new Marking([]),
            new Transition(DossierStatusTransition::PUBLISH_AS_PREVIEW->value, [], []),
        );

        $this->guard->guardPublicationAsPreview($event);

        self::assertFalse($event->isBlocked());
    }
}
