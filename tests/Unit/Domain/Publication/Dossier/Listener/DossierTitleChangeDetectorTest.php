<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Listener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mockery;
use Shared\Domain\Publication\Dossier\Event\DossierTitleChangedEvent;
use Shared\Domain\Publication\Dossier\Listener\DossierTitleChangeDetector;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DossierTitleChangeDetectorTest extends UnitTestCase
{
    public function testEventDispatchedWhenDossierTitleChanges(): void
    {
        $dossierId = Uuid::v6();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $oldTitle = DossierTitle::create('Old Title');
        $newTitle = DossierTitle::create('New Title');

        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('title')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('title')->andReturn($oldTitle);
        $preUpdateArgs->expects('getNewValue')->with('title')->andReturn($newTitle);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->expects('dispatch')->with(Mockery::on(
            static function (DossierTitleChangedEvent $event) use ($dossierId, $oldTitle, $newTitle): bool {
                return $event->dossierId === $dossierId
                    && $event->oldDossierTitle === $oldTitle
                    && $event->newDossierTitle === $newTitle;
            },
        ));

        $listener = new DossierTitleChangeDetector($dispatcher);
        $listener->preUpdate($dossier, $preUpdateArgs);
        $listener->postUpdate($dossier, Mockery::mock(PostUpdateEventArgs::class));
    }

    public function testNoEventWhenFieldNotChanged(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('title')->andReturnFalse();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierTitleChangeDetector($dispatcher);
        $listener->preUpdate(Mockery::mock(WooDecision::class), $preUpdateArgs);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }

    public function testNoEventInPostUpdateWithoutPriorPreUpdate(): void
    {
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierTitleChangeDetector($dispatcher);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }
}
