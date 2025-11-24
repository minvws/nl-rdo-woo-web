<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use Shared\Domain\Publication\Dossier\Admin\DossierListingService;
use Shared\Domain\Publication\Dossier\Admin\DossierQueryConditions;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DossierListingServiceTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private DossierListingService $listingService;
    private Organisation&MockInterface $organisation;
    private DossierQueryConditions&MockInterface $queryConditions;
    private TranslatorInterface&MockInterface $translator;

    protected function setUp(): void
    {
        $this->organisation = \Mockery::mock(Organisation::class);

        $this->dossierRepository = \Mockery::mock(DossierRepository::class);

        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $this->authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($this->organisation);

        $this->dossierTypeManager = \Mockery::mock(DossierTypeManager::class);
        $this->queryConditions = \Mockery::mock(DossierQueryConditions::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);

        $this->listingService = new DossierListingService(
            $this->dossierRepository,
            $this->authorizationMatrix,
            $this->dossierTypeManager,
            $this->queryConditions,
            $this->translator,
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

    public function testGetFilteredListingQueryReturnsBaseQueryFilterParamsAreEmpty(): void
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
            $this->listingService->getFilteredListingQuery(new DossierFilterParameters()),
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

    public function testGetAvailableTypesOrderedByName(): void
    {
        $configA = \Mockery::mock(DossierTypeConfigInterface::class);
        $configA->shouldReceive('getDossierType')->andReturn(DossierType::WOO_DECISION);

        $configB = \Mockery::mock(DossierTypeConfigInterface::class);
        $configB->shouldReceive('getDossierType')->andReturn(DossierType::COVENANT);

        $this->translator->shouldReceive('trans')->with('dossier.type.woo-decision', [], [], null)->andReturn('W');
        $this->translator->shouldReceive('trans')->with('dossier.type.covenant', [], [], null)->andReturn('C');

        $this->dossierTypeManager->expects('getAvailableConfigs')->andReturn([$configA, $configB]);

        self::assertEquals(
            [DossierType::COVENANT, DossierType::WOO_DECISION],
            $this->listingService->getAvailableTypesOrderedByName(),
        );
    }
}
