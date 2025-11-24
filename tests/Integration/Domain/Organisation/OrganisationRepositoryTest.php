<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Organisation;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Integration\SharedWebTestCase;

class OrganisationRepositoryTest extends SharedWebTestCase
{
    private OrganisationRepository $organisationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = self::getContainer()->get(OrganisationRepository::class);
    }

    public function testGetPaginated(): void
    {
        $organisationCount = $this->getFaker()->numberBetween(1, 5);
        OrganisationFactory::createMany($organisationCount);

        $result = $this->organisationRepository->getPaginated(100, null);

        self::assertCount($organisationCount, $result);
        self::assertContainsOnlyInstancesOf(Organisation::class, $result);
    }
}
