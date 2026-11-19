<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Shared\Domain\Event\DossierChangedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\EventSubscriber\DossierChangedCollection;
use Shared\EventSubscriber\DossierChangedCollectionBuilder;
use Shared\Tests\Unit\UnitTestCase;

class DossierChangedCollectionBuilderTest extends UnitTestCase
{
    public function testOnDossierChangedAddsDossierIdToCollection(): void
    {
        $wooDecision = new WooDecision();
        $collection = new DossierChangedCollection();

        $builder = new DossierChangedCollectionBuilder($collection);
        $builder->onDossierChanged(new DossierChangedEvent($wooDecision));

        self::assertEquals([$wooDecision->getId()], $collection->claim());
    }
}
