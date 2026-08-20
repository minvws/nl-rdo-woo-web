<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Listener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Domain\Publication\Dossier\Listener\DossierNumberChangeDetector;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DossierNumberChangeDetectorTest extends UnitTestCase
{
    public function testEventDispatchedWhenDossierNumberChanges(): void
    {
        $dossierId = Uuid::v6();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->andReturn($dossierId);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('dossierNumber')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('dossierNumber')->andReturn('old-nr');
        $preUpdateArgs->expects('getNewValue')->with('dossierNumber')->andReturn('new-nr');

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->expects('dispatch')->with(Mockery::on(
            static function (DossierNumberChangedEvent $event) use ($dossierId): bool {
                return $event->dossierId === $dossierId
                    && $event->oldDossierNumber === 'old-nr'
                    && $event->newDossierNumber === 'new-nr'
                    && $event->status === DossierStatus::PUBLISHED;
            },
        ));

        $listener = new DossierNumberChangeDetector($dispatcher);
        $listener->preUpdate($dossier, $preUpdateArgs);
        $listener->postUpdate($dossier, Mockery::mock(PostUpdateEventArgs::class));
    }

    public function testNoEventWhenFieldNotChanged(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('dossierNumber')->andReturnFalse();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierNumberChangeDetector($dispatcher);
        $listener->preUpdate(Mockery::mock(WooDecision::class), $preUpdateArgs);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }

    public function testNoEventWhenOldValueIsEmpty(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('dossierNumber')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('dossierNumber')->andReturn('');

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierNumberChangeDetector($dispatcher);
        $listener->preUpdate(Mockery::mock(WooDecision::class), $preUpdateArgs);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }

    public function testNoEventWhenOldValueIsNull(): void
    {
        $preUpdateArgs = Mockery::mock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects('hasChangedField')->with('dossierNumber')->andReturnTrue();
        $preUpdateArgs->expects('getOldValue')->with('dossierNumber')->andReturnNull();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierNumberChangeDetector($dispatcher);
        $listener->preUpdate(Mockery::mock(WooDecision::class), $preUpdateArgs);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }

    public function testNoEventInPostUpdateWithoutPriorPreUpdate(): void
    {
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $listener = new DossierNumberChangeDetector($dispatcher);
        $listener->postUpdate(Mockery::mock(WooDecision::class), Mockery::mock(PostUpdateEventArgs::class));

        $dispatcher->shouldNotHaveReceived('dispatch');
    }
}
