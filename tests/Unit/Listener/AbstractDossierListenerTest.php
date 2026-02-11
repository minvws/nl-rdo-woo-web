<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Listener;

use Mockery;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Listener\AbstractDossierListener;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AbstractDossierListenerTest extends SharedWebTestCase
{
    public function testPostPersistDispatchesEvent(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                }),
            );

        $listener = new AbstractDossierListener($dispatcher);
        $listener->postPersist($wooDecision);
    }

    public function testPostUpdateDispatchesEvent(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                }),
            );

        $listener = new AbstractDossierListener($dispatcher);
        $listener->postUpdate($wooDecision);
    }
}
