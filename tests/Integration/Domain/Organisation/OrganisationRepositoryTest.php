<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Organisation;

use Carbon\CarbonImmutable;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Integration\SharedWebTestCase;

class OrganisationRepositoryTest extends SharedWebTestCase
{
    public function testGetPaginated(): void
    {
        $organisationCount = $this->getFaker()->numberBetween(1, 5);
        OrganisationFactory::createMany($organisationCount);

        $result = self::fromContainer(OrganisationRepository::class)->getPaginated(100, null);

        self::assertCount($organisationCount, $result);
        self::assertContainsOnlyInstancesOf(Organisation::class, $result);
    }

    public function testHasAny(): void
    {
        $repository = self::fromContainer(OrganisationRepository::class);

        self::assertFalse($repository->hasAny());

        OrganisationFactory::createMany(2);

        self::assertTrue($repository->hasAny());
    }

    public function testOldestReturnsNullWithoutOrganisations(): void
    {
        self::assertNull(self::fromContainer(OrganisationRepository::class)->oldest());
    }

    public function testOldest(): void
    {
        $repository = self::fromContainer(OrganisationRepository::class);

        OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()->subDay()]);
        $oldest = OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()->subWeek()]);
        OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()]);

        self::assertSame($oldest->getId()->toRfc4122(), $repository->oldest()?->getId()->toRfc4122());
    }

    public function testGetLimitedOrdersByCreatedAtAndAppliesLimit(): void
    {
        $repository = self::fromContainer(OrganisationRepository::class);

        self::assertCount(0, $repository->getLimited());

        $newest = OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()]);
        $oldest = OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()->subWeek()]);
        $middle = OrganisationFactory::createOne(['createdAt' => CarbonImmutable::now()->subDay()]);

        $toId = static fn (Organisation $organisation): string => $organisation->getId()->toRfc4122();

        self::assertSame(
            [$oldest->getId()->toRfc4122(), $middle->getId()->toRfc4122(), $newest->getId()->toRfc4122()],
            $repository->getLimited()->map($toId)->toArray(),
        );

        self::assertSame(
            [$oldest->getId()->toRfc4122(), $middle->getId()->toRfc4122()],
            $repository->getLimited(2)->map($toId)->toArray(),
        );
    }
}
