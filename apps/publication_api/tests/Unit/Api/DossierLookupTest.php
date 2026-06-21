<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api;

use Mockery;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

class DossierLookupTest extends UnitTestCase
{
    public function testFindReturnsDossierWhenFound(): void
    {
        $externalId = Mockery::mock(ExternalId::class);
        $orgId = Mockery::mock(Uuid::class);

        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($orgId);

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierOrganisation = Mockery::mock(Organisation::class);
        $dossierOrganisation->expects('getId')->andReturn($orgId);
        $dossier->expects('getOrganisation')->andReturn($dossierOrganisation);

        $orgId->expects('equals')->with($orgId)->andReturnTrue();

        $repository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $repository->expects('findByOrganisationAndExternalId')
            ->with($organisation, $externalId)
            ->andReturn($dossier);

        $result = new DossierLookup()->find($repository, $organisation, $externalId);

        $this->assertSame($dossier, $result);
    }

    public function testFindThrowsWhenDossierNotFound(): void
    {
        $externalId = ExternalId::create('test-external-id');

        $organisation = Mockery::mock(Organisation::class);

        $repository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $repository->expects('findByOrganisationAndExternalId')
            ->with($organisation, $externalId)
            ->andReturnNull();

        $this->expectException(EntityNotFoundException::class);

        new DossierLookup()->find($repository, $organisation, $externalId);
    }

    public function testFindThrowsWhenDossierBelongsToDifferentOrganisation(): void
    {
        $externalId = ExternalId::create('test-external-id');
        $orgId = Mockery::mock(Uuid::class);
        $otherOrgId = Mockery::mock(Uuid::class);

        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($orgId);

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierOrganisation = Mockery::mock(Organisation::class);
        $dossierOrganisation->expects('getId')->andReturn($otherOrgId);
        $dossier->expects('getOrganisation')->andReturn($dossierOrganisation);

        $orgId->expects('equals')->with($otherOrgId)->andReturnFalse();

        $repository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $repository->expects('findByOrganisationAndExternalId')
            ->with($organisation, $externalId)
            ->andReturn($dossier);

        $this->expectException(EntityNotFoundException::class);

        new DossierLookup()->find($repository, $organisation, $externalId);
    }
}
