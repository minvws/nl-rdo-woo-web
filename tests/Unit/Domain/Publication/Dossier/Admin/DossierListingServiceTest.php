<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use App\Domain\Publication\Dossier\Admin\DossierListingService;
use App\Domain\Publication\Dossier\Admin\DossierQueryConditions;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Entity\Department;
use App\Entity\Organisation;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierListingServiceTest extends MockeryTestCase
{
    private AbstractDossierRepository&MockInterface $dossierRepository;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private DossierListingService $listingService;
    private Organisation&MockInterface $organisation;
    private DossierQueryConditions&MockInterface $queryConditions;

    public function setUp(): void
    {
        $this->organisation = \Mockery::mock(Organisation::class);

        $this->dossierRepository = \Mockery::mock(AbstractDossierRepository::class);

        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $this->authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($this->organisation);

        $this->dossierTypeManager = \Mockery::mock(DossierTypeManager::class);

        $this->queryConditions = \Mockery::mock(DossierQueryConditions::class);

        $this->listingService = new DossierListingService(
            $this->dossierRepository,
            $this->authorizationMatrix,
            $this->dossierTypeManager,
            $this->queryConditions,
        );
    }

    public function testGetFilteredListingQueryReturnsBaseQueryWhenNoFilterParametersAreProvided(): void
    {
        $queryBuilder = \Mockery::mock(QueryBuilder::class);

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $config = \Mockery::mock(DossierTypeConfigInterface::class);
        $config->shouldReceive('getDossierType')->andReturn(DossierType::COVENANT);

        $this->dossierTypeManager->expects('getAvailableConfigs')->andReturn([$config]);

        $this->dossierRepository
            ->expects('getDossiersForOrganisationQueryBuilder')
            ->with(
                $this->organisation,
                [
                    DossierStatus::SCHEDULED,
                    DossierStatus::PREVIEW,
                    DossierStatus::PUBLISHED,
                    DossierStatus::NEW,
                    DossierStatus::CONCEPT,
                ],
                [DossierType::COVENANT],
            )
            ->andReturn($queryBuilder);

        self::assertEquals(
            $queryBuilder,
            $this->listingService->getFilteredListingQuery(null),
        );
    }

    public function testGetFilteredListingQueryAppliesAllFilterParameters(): void
    {
        $queryBuilder = \Mockery::mock(QueryBuilder::class);

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $config = \Mockery::mock(DossierTypeConfigInterface::class);
        $config->shouldReceive('getDossierType')->andReturn(DossierType::COVENANT);

        $this->dossierTypeManager->expects('getAvailableConfigs')->andReturn([$config]);

        $this->dossierRepository
            ->expects('getDossiersForOrganisationQueryBuilder')
            ->with(
                $this->organisation,
                [
                    DossierStatus::SCHEDULED,
                    DossierStatus::PREVIEW,
                    DossierStatus::PUBLISHED,
                    DossierStatus::NEW,
                    DossierStatus::CONCEPT,
                ],
                [DossierType::COVENANT],
            )
            ->andReturn($queryBuilder);

        $filterParams = new DossierFilterParameters();
        $filterParams->statuses = [DossierStatus::PREVIEW];
        $filterParams->types = [DossierType::WOO_DECISION];
        $filterParams->departments = new ArrayCollection([\Mockery::mock(Department::class)]);

        $this->queryConditions
            ->expects('filterOnStatuses')
            ->with($queryBuilder, ...$filterParams->statuses);

        $this->queryConditions
            ->expects('filterOnTypes')
            ->with($queryBuilder, ...$filterParams->types);

        $this->queryConditions
            ->expects('filterOnDepartments')
            ->with($queryBuilder, ...$filterParams->departments->toArray());

        self::assertEquals(
            $queryBuilder,
            $this->listingService->getFilteredListingQuery($filterParams),
        );
    }

    public function testGetAllowedStatusesReturnsEmptyArrayWhenNoFiltersAreEnabled(): void
    {
        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnFalse();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnFalse();

        self::assertEquals(
            [],
            $this->listingService->getAllowedStatuses(),
        );
    }

    public function testGetAllowedStatusesReturnsOnlyPublishedStates(): void
    {
        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnFalse();

        self::assertEquals(
            DossierStatus::nonConceptCases(),
            $this->listingService->getAllowedStatuses(),
        );
    }

    public function testGetAllowedStatusesReturnsOnlyUnpublishedStates(): void
    {
        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnFalse();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnTrue();

        self::assertEquals(
            DossierStatus::conceptCases(),
            $this->listingService->getAllowedStatuses(),
        );
    }

    public function testGetAllowedStatusesReturnsAllStates(): void
    {
        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)
            ->andReturnTrue();

        self::assertEquals(
            array_merge(DossierStatus::nonConceptCases(), DossierStatus::conceptCases()),
            $this->listingService->getAllowedStatuses(),
        );
    }

    public function testGetAvailableTypes(): void
    {
        $configA = \Mockery::mock(DossierTypeConfigInterface::class);
        $configA->shouldReceive('getDossierType')->andReturn(DossierType::COVENANT);

        $configB = \Mockery::mock(DossierTypeConfigInterface::class);
        $configB->shouldReceive('getDossierType')->andReturn(DossierType::WOO_DECISION);

        $this->dossierTypeManager->expects('getAvailableConfigs')->andReturn([$configA, $configB]);

        self::assertEquals(
            [DossierType::COVENANT, DossierType::WOO_DECISION],
            $this->listingService->getAvailableTypes(),
        );
    }
}
