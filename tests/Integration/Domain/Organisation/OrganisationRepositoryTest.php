<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Organisation;

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
}
