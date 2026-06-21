<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api;

use Mockery;
use PublicationApi\Api\OrganisationLookup;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Tests\Unit\UnitTestCase;

class OrganisationLookupTest extends UnitTestCase
{
    public function testFindReturnsOrganisationWhenFound(): void
    {
        $organisationId = 'org-123';
        $organisation = Mockery::mock(Organisation::class);

        $repository = Mockery::mock(OrganisationRepository::class);
        $repository->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $result = new OrganisationLookup($repository)->find($organisationId);

        $this->assertSame($organisation, $result);
    }

    public function testFindThrowsWhenOrganisationNotFound(): void
    {
        $organisationId = 'org-not-exist';

        $repository = Mockery::mock(OrganisationRepository::class);
        $repository->expects('find')
            ->with($organisationId)
            ->andReturnNull();

        $this->expectException(EntityNotFoundException::class);

        new OrganisationLookup($repository)->find($organisationId);
    }
}
