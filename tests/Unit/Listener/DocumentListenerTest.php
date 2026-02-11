<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Listener;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Listener\DocumentListener;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DocumentListenerTest extends SharedWebTestCase
{
    public function testPostPersistDispatchesEventForEachDossier(): void
    {
        $wooDecision1 = WooDecisionFactory::createOne();
        $wooDecision2 = WooDecisionFactory::createOne();

        $document = Mockery::mock(Document::class);
        $document
            ->expects('getDossiers')
            ->andReturn(new ArrayCollection([$wooDecision1, $wooDecision2]));

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->times(2);

        $listener = new DocumentListener($dispatcher);
        $listener->postPersist($document);
    }

    public function testPostUpdateDispatchesEventForEachDossier(): void
    {
        $wooDecision1 = WooDecisionFactory::createOne();
        $wooDecision2 = WooDecisionFactory::createOne();

        $document = Mockery::mock(Document::class);
        $document
            ->expects('getDossiers')
            ->andReturn(new ArrayCollection([$wooDecision1, $wooDecision2]));

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->times(2);

        $listener = new DocumentListener($dispatcher);
        $listener->postUpdate($document);
    }

    public function testPostRemoveDispatchesEventForEachDossier(): void
    {
        $wooDecision1 = WooDecisionFactory::createOne();
        $wooDecision2 = WooDecisionFactory::createOne();

        $document = Mockery::mock(Document::class);
        $document
            ->expects('getDossiers')
            ->andReturn(new ArrayCollection([$wooDecision1, $wooDecision2]));

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->times(2);

        $listener = new DocumentListener($dispatcher);
        $listener->postRemove($document);
    }

    public function testDocumentWithMultipleDossiersDispatchesEventForEachDossierWithCorrectData(): void
    {
        $wooDecision1 = WooDecisionFactory::createOne();
        $wooDecision2 = WooDecisionFactory::createOne();
        $wooDecision3 = WooDecisionFactory::createOne();

        $document = Mockery::mock(Document::class);
        $document
            ->expects('getDossiers')
            ->andReturn(new ArrayCollection([$wooDecision1, $wooDecision2, $wooDecision3]));

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->expects('dispatch')
            ->with(Mockery::on(static function (DossierChangedEvent $event) use ($wooDecision1): bool {
                return $event->getDossier() === $wooDecision1;
            }));
        $dispatcher->expects('dispatch')
            ->with(Mockery::on(static function (DossierChangedEvent $event) use ($wooDecision2): bool {
                return $event->getDossier() === $wooDecision2;
            }));
        $dispatcher->expects('dispatch')
            ->with(Mockery::on(static function (DossierChangedEvent $event) use ($wooDecision3): bool {
                return $event->getDossier() === $wooDecision3;
            }));

        $listener = new DocumentListener($dispatcher);
        $listener->postPersist($document);
    }
}
