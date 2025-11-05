<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\History\DossierEntityUpdateListener;
use App\Domain\Publication\History\History;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class DossierEntityUpdateListenerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierEntityUpdateListener $listener;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->listener = new DossierEntityUpdateListener(
            $this->entityManager,
        );

        parent::setUp();
    }

    public function testHistoryLoggingForUpdate(): void
    {
        $preUpdateArgs = \Mockery::mock(PreUpdateEventArgs::class);
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());
        $dossier->shouldReceive('getPreviewDate')->andReturn(new \DateTimeImmutable());

        $preUpdateArgs->shouldReceive('hasChangedField')->with('decisionDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('decisionDate')->andReturn('foo');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('title')->andReturn('foo');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('summary')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('summary')->andReturn('foo');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('publicationDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('publicationDate')->andReturn('foo');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('previewDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('previewDate')->andReturn('foo');

        $this->listener->preUpdate($dossier, $preUpdateArgs);

        $postUpdateArgs = \Mockery::mock(PostUpdateEventArgs::class);

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_updated'
        ));

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_update_publication_date'
        ));

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_update_preview_date'
        ));

        $this->entityManager->expects('flush');

        $this->listener->postUpdate($dossier, $postUpdateArgs);
    }

    public function testHistoryLoggingSkipsInitialValues(): void
    {
        $preUpdateArgs = \Mockery::mock(PreUpdateEventArgs::class);
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());
        $dossier->shouldReceive('getPreviewDate')->andReturn(new \DateTimeImmutable());

        $preUpdateArgs->shouldReceive('hasChangedField')->with('decisionDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('decisionDate')->andReturnNull();

        $preUpdateArgs->shouldReceive('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('title')->andReturn('');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('summary')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('summary')->andReturn('');

        $preUpdateArgs->shouldReceive('hasChangedField')->with('publicationDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('publicationDate')->andReturnNull();

        $preUpdateArgs->shouldReceive('hasChangedField')->with('previewDate')->andReturnTrue();
        $preUpdateArgs->shouldReceive('getOldValue')->with('previewDate')->andReturnNull();

        $this->listener->preUpdate($dossier, $preUpdateArgs);

        $postUpdateArgs = \Mockery::mock(PostUpdateEventArgs::class);

        $this->entityManager->shouldNotHaveReceived('persist');
        $this->entityManager->shouldNotHaveReceived('flush');

        $this->listener->postUpdate($dossier, $postUpdateArgs);
    }
}
