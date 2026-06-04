<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Shared\EventSubscriber\DossierChangedCollection;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DossierChangedCollectionTest extends UnitTestCase
{
    public function testAddDossierIdDeduplicatesSameUuid(): void
    {
        $collection = new DossierChangedCollection();

        $uuid = Uuid::v6();

        $uuidA = $uuid;
        $uuidB = $uuid;
        $uuidC = Uuid::v6();

        $collection->addDossierId($uuidA);
        $collection->addDossierId($uuidB);
        $collection->addDossierId($uuidA);
        $collection->addDossierId($uuidC);

        $claimed = $collection->claim();

        self::assertCount(2, $claimed);
        self::assertContains($uuid, $claimed);
        self::assertContains($uuidC, $claimed);
    }

    public function testAddDossierIdPreservesInsertionOrder(): void
    {
        $collection = new DossierChangedCollection();

        $uuid1 = Uuid::v6();
        $uuid2 = Uuid::v6();
        $uuid3 = Uuid::v6();

        $collection->addDossierId($uuid1);
        $collection->addDossierId($uuid2);
        $collection->addDossierId($uuid3);
        $collection->addDossierId(Uuid::fromString($uuid2->toRfc4122())); // duplicate, should be ignored

        $claimed = $collection->claim();

        self::assertCount(3, $claimed);
        self::assertTrue($uuid1->equals($claimed[0]));
        self::assertTrue($uuid2->equals($claimed[1]));
        self::assertTrue($uuid3->equals($claimed[2]));
    }

    public function testClaimReturnsEmptyWhenCollectionIsEmpty(): void
    {
        $collection = new DossierChangedCollection();

        self::assertSame([], $collection->claim());
    }

    public function testClaimIsIdempotentWhenCalledTwice(): void
    {
        $collection = new DossierChangedCollection();
        $collection->addDossierId(Uuid::v6());

        $first = $collection->claim();
        $second = $collection->claim();

        self::assertCount(1, $first);
        self::assertSame([], $second);
    }

    public function testDeleteDossierIdRemovesUuidFromPending(): void
    {
        $collection = new DossierChangedCollection();
        $uuid = Uuid::v6();
        $collection->addDossierId($uuid);

        $collection->claim();
        $collection->removeDossierIdFromCollection($uuid);
        $collection->reset();

        self::assertSame([], $collection->claim());
    }

    public function testResetClearsCollectionAndAllowsNewClaim(): void
    {
        $collection = new DossierChangedCollection();
        $uuid = Uuid::v6();
        $collection->addDossierId($uuid);

        $collection->claim();
        $collection->reset();

        $newUuid = Uuid::v6();
        $collection->addDossierId($newUuid);

        $claimed = $collection->claim();

        self::assertCount(1, $claimed);
        self::assertTrue($newUuid->equals($claimed[0]));
    }
}
