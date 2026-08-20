<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Command;

use Mockery;
use Shared\Domain\Event\DeduplicatableEvent;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class SynchronizeDossierArtifactsCommandTest extends UnitTestCase
{
    public function testItIsADeduplicatableEvent(): void
    {
        $command = new SynchronizeDossierArtifactsCommand(Uuid::v6());

        self::assertInstanceOf(DeduplicatableEvent::class, $command);
    }

    public function testDeduplicationKeyIsBasedOnTheClassAndDossierUuid(): void
    {
        $uuid = Uuid::v6();

        $command = new SynchronizeDossierArtifactsCommand($uuid);

        self::assertSame(
            SynchronizeDossierArtifactsCommand::class . ':' . $uuid->toRfc4122(),
            $command->deduplicationKey(),
        );
    }

    public function testDeduplicationKeyIsEqualForTheSameDossier(): void
    {
        $uuid = Uuid::v6();

        $commandA = new SynchronizeDossierArtifactsCommand($uuid);
        $commandB = new SynchronizeDossierArtifactsCommand(Uuid::fromString($uuid->toRfc4122()));

        self::assertSame($commandA->deduplicationKey(), $commandB->deduplicationKey());
    }

    public function testDeduplicationKeyDiffersPerDossier(): void
    {
        $commandA = new SynchronizeDossierArtifactsCommand(Uuid::v6());
        $commandB = new SynchronizeDossierArtifactsCommand(Uuid::v6());

        self::assertNotSame($commandA->deduplicationKey(), $commandB->deduplicationKey());
    }

    public function testDeduplicationKeyForACommandCreatedForADossier(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($uuid = Uuid::v6());

        $command = SynchronizeDossierArtifactsCommand::forDossier($dossier);

        self::assertSame(
            SynchronizeDossierArtifactsCommand::class . ':' . $uuid->toRfc4122(),
            $command->deduplicationKey(),
        );
    }
}
