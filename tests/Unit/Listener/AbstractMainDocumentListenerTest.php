<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Listener;

use Mockery;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Listener\AbstractMainDocumentListener;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AbstractMainDocumentListenerTest extends SharedWebTestCase
{
    public function testPostPersistExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                })
            );

        $listener = new AbstractMainDocumentListener($dispatcher);
        $listener->postPersist($mainDocument);
    }

    public function testPostUpdateExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                })
            );

        $listener = new AbstractMainDocumentListener($dispatcher);
        $listener->postUpdate($mainDocument);
    }

    public function testPostRemoveExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                })
            );

        $listener = new AbstractMainDocumentListener($dispatcher);
        $listener->postRemove($mainDocument);
    }
}
