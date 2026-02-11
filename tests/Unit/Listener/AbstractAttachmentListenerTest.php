<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Listener;

use Mockery;
use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Listener\AbstractAttachmentListener;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AbstractAttachmentListenerTest extends SharedWebTestCase
{
    public function testPostPersistExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                }),
            );

        $listener = new AbstractAttachmentListener($dispatcher);
        $listener->postPersist($attachment);
    }

    public function testPostUpdateExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                }),
            );

        $listener = new AbstractAttachmentListener($dispatcher);
        $listener->postUpdate($attachment);
    }

    public function testPostRemoveExtractsDossierAndDispatches(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment
            ->expects('getDossier')
            ->andReturn($wooDecision);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher
            ->expects('dispatch')
            ->with(
                Mockery::on(function (DossierChangedEvent $event) use ($wooDecision) {
                    return $event->getDossier() === $wooDecision;
                }),
            );

        $listener = new AbstractAttachmentListener($dispatcher);
        $listener->postRemove($attachment);
    }
}
