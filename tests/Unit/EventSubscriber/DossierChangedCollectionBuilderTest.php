<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Shared\Domain\Event\DossierChangedEvent;
use Shared\EventSubscriber\DossierChangedCollectionBuilder;
use Shared\Tests\Unit\UnitTestCase;

class DossierChangedCollectionBuilderTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        $expectedSubscribedEvents = [
            DossierChangedEvent::class => 'onDossierChanged',
        ];
        $subscribedEvents = DossierChangedCollectionBuilder::getSubscribedEvents();

        self::assertEquals($expectedSubscribedEvents, $subscribedEvents);
    }
}
