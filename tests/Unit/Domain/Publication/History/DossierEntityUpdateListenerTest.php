<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\History\DossierEntityUpdateListener;
use App\Entity\History;
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

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->listener = new DossierEntityUpdateListener(
            $this->entityManager,
        );

        parent::setUp();
    }

    public function testHistoryLogging(): void
    {
        $preUpdateArgs = \Mockery::mock(PreUpdateEventArgs::class);
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());
        $dossier->shouldReceive('getPreviewDate')->andReturn(new \DateTimeImmutable());

        $preUpdateArgs->expects('hasChangedField')->with('decisionDate')->andReturnTrue();
        $preUpdateArgs->expects('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->expects('hasChangedField')->with('summary')->andReturnTrue();
        $preUpdateArgs->expects('hasChangedField')->with('publicationDate')->andReturnTrue();
        $preUpdateArgs->expects('hasChangedField')->with('previewDate')->andReturnTrue();

        $this->listener->preUpdate($dossier, $preUpdateArgs);

        $postUpdateArgs = \Mockery::mock(PostUpdateEventArgs::class);

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static function (History $history): bool {
                return $history->getContextKey() === 'dossier_updated';
            }
        ));

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static function (History $history): bool {
                return $history->getContextKey() === 'dossier_update_publication_date';
            }
        ));

        $this->entityManager->expects('persist')->with(\Mockery::on(
            static function (History $history): bool {
                return $history->getContextKey() === 'dossier_update_preview_date';
            }
        ));

        $this->entityManager->expects('flush');

        $this->listener->postUpdate($dossier, $postUpdateArgs);
    }
}
