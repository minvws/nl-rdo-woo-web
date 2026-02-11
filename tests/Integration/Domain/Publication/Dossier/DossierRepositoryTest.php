<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier;

use Doctrine\ORM\NoResultException;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

use function array_map;

final class DossierRepositoryTest extends SharedWebTestCase
{
    private DossierRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(DossierRepository::class);
    }

    public function testRemove(): void
    {
        $dossier = CovenantFactory::createOne();

        $result = $this->repository->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $this->repository->remove($result);

        $this->expectException(NoResultException::class);
        $this->repository->findOneByDossierId($dossier->getId());
    }

    public function testGetDossiersForOrganisationQueryBuilder(): void
    {
        $covenant = CovenantFactory::createOne();

        $result = $this->repository->getDossiersForOrganisationQueryBuilder(
            $covenant->getOrganisation(),
            [$covenant->getStatus()],
            [$covenant->getType()],
        )->getQuery()->getResult();

        self::assertCount(1, $result);
    }

    public function testFindDossiersPendingPublication(): void
    {
        $covenant = CovenantFactory::createOne(['status' => DossierStatus::SCHEDULED, 'completed' => true]);
        $wooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW, 'completed' => true]);
        $annualReport = AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'completed' => true]);
        $published = AnnualReportFactory::createOne(['status' => DossierStatus::PUBLISHED, 'completed' => false]);
        $uncompleted = AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'completed' => false]);

        $result = $this->repository->findDossiersPendingPublication();
        $ids = array_map(
            static fn (AbstractDossier $dossier) => $dossier->getId()->toRfc4122(),
            $result,
        );

        self::assertContains($covenant->getId()->toRfc4122(), $ids);
        self::assertContains($wooDecision->getId()->toRfc4122(), $ids);
        self::assertContains($annualReport->getId()->toRfc4122(), $ids);
        self::assertNotContains($published->getId()->toRfc4122(), $ids);
        self::assertContains($uncompleted->getId()->toRfc4122(), $ids);
    }

    public function testGetRecentDossiersWithoutDepartmentFiltersUnpublishedAndLimitsResults(): void
    {
        $unpublished = AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT]);
        CovenantFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        AnnualReportFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        AnnualReportFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        InvestigationReportFactory::createOne(['status' => DossierStatus::PUBLISHED]);

        $result = $this->repository->getRecentDossiers(4, null);
        $ids = array_map(
            static fn (AbstractDossier $dossier) => $dossier->getId()->toRfc4122(),
            $result,
        );

        self::assertCount(4, $result);
        self::assertNotContains($unpublished->getId()->toRfc4122(), $ids);
    }

    public function testGetRecentDossiersWithDepartmentFilter(): void
    {
        $department = DepartmentFactory::createOne();

        AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'departments' => [$department]]);
        $publishedForDepartment = CovenantFactory::createOne(['status' => DossierStatus::PUBLISHED, 'departments' => [$department]]);
        CovenantFactory::createOne(['status' => DossierStatus::PUBLISHED, 'departments' => []]);

        $result = $this->repository->getRecentDossiers(4, $department);
        $ids = array_map(
            static fn (AbstractDossier $dossier) => $dossier->getId()->toRfc4122(),
            $result,
        );

        self::assertEquals([$publishedForDepartment->getId()->toRfc4122()], $ids);
    }
}
