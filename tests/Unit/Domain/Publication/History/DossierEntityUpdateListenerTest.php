<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\History\DossierEntityUpdateListener;
use Shared\Domain\Publication\History\History;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class DossierEntityUpdateListenerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierEntityUpdateListener $listener;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->listener = new DossierEntityUpdateListener(
            $this->entityManager,
        );

        parent::setUp();
    }

    public function testHistoryLoggingForUpdate(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->times(3)->andReturn(Uuid::v6());
        $dossier->expects('getPublicationDate')->andReturn(PlainDate::today());
        $dossier->expects('getPreviewDate')->andReturn(PlainDate::today());

        $preUpdateArgs->expects('hasChangedField')->with('decisionDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('decisionDate')->andReturn('foo');

        $preUpdateArgs->expects('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('title')->andReturn('foo');

        $preUpdateArgs->expects('hasChangedField')->with('summary')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('summary')->andReturn('foo');

        $preUpdateArgs->expects('hasChangedField')->with('publicationDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('publicationDate')->andReturn('foo');

        $preUpdateArgs->expects('hasChangedField')->with('previewDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('previewDate')->andReturn('foo');

        $this->listener->preUpdate($dossier, $preUpdateArgs);

        $postUpdateArgs = Mockery::mock(PostUpdateEventArgs::class);

        $this->entityManager->expects('persist')->with(Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_updated',
        ));

        $this->entityManager->expects('persist')->with(Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_update_publication_date',
        ));

        $this->entityManager->expects('persist')->with(Mockery::on(
            static fn (History $history): bool => $history->getContextKey() === 'dossier_update_preview_date',
        ));

        $this->entityManager->expects('flush');

        $this->listener->postUpdate($dossier, $postUpdateArgs);
    }

    public function testHistoryLoggingSkipsInitialValues(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $dossier = Mockery::mock(WooDecision::class);

        $preUpdateArgs->expects('hasChangedField')->with('decisionDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('decisionDate')->andReturnNull();

        $preUpdateArgs->expects('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('title')->andReturn('');

        $preUpdateArgs->expects('hasChangedField')->with('summary')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->times(2)->with('summary')->andReturn('');

        $preUpdateArgs->expects('hasChangedField')->with('publicationDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('publicationDate')->andReturnNull();

        $preUpdateArgs->expects('hasChangedField')->with('previewDate')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('previewDate')->andReturnNull();

        $this->listener->preUpdate($dossier, $preUpdateArgs);

        $postUpdateArgs = Mockery::mock(PostUpdateEventArgs::class);

        $this->entityManager->shouldNotHaveReceived('persist');
        $this->entityManager->shouldNotHaveReceived('flush');

        $this->listener->postUpdate($dossier, $postUpdateArgs);
    }
}
