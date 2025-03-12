<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Tests\Factory\DepartmentFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DossierRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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

        $result = $this->repository->findOneByDossierId($dossier->_real()->getId());
        self::assertEquals($dossier->_real()->getId(), $result->getId());

        $this->repository->remove($result);

        $this->expectException(NoResultException::class);
        $this->repository->findOneByDossierId($dossier->_real()->getId());
    }

    public function testFindBySearchTerm(): void
    {
        $covenant = CovenantFactory::createOne();

        $result = $this->repository->findBySearchTerm(
            $covenant->_real()->getDossierNr(),
            10,
            $covenant->_real()->getOrganisation(),
        );

        self::assertCount(1, $result);
    }

    public function testFindBySearchTermFilteredByType(): void
    {
        $organisation = OrganisationFactory::createOne();

        $covenant = CovenantFactory::createOne([
            'title' => 'A Convenant Foobar',
            'organisation' => $organisation,
        ]);
        AnnualReportFactory::createOne([
            'title' => 'An AnnualReport Foobar',
            'organisation' => $organisation,
        ]);

        $result = $this->repository->findBySearchTerm(
            'FooBAR',
            10,
            $covenant->_real()->getOrganisation(),
            dossierType: DossierType::COVENANT,
        );

        self::assertCount(1, $result);
    }

    public function testFindBySearchTermFilteredByUuid(): void
    {
        $organisation = OrganisationFactory::createOne();

        $covenant = CovenantFactory::createOne([
            'title' => 'A Convenant Foobar',
            'organisation' => $organisation,
        ]);
        CovenantFactory::createOne([
            'title' => 'An AnnualReport Foobar',
            'organisation' => $organisation,
        ]);

        $result = $this->repository->findBySearchTerm(
            'FooBAR',
            10,
            $covenant->_real()->getOrganisation(),
            dossierId: $covenant->_real()->getId(),
        );

        self::assertCount(1, $result);
    }

    public function testGetDossiersForOrganisationQueryBuilder(): void
    {
        $covenant = CovenantFactory::createOne();

        $result = $this->repository->getDossiersForOrganisationQueryBuilder(
            $covenant->_real()->getOrganisation(),
            [$covenant->_real()->getStatus()],
            [$covenant->_real()->getType()],
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

        self::assertContains($covenant->_real()->getId()->toRfc4122(), $ids);
        self::assertContains($wooDecision->_real()->getId()->toRfc4122(), $ids);
        self::assertContains($annualReport->_real()->getId()->toRfc4122(), $ids);
        self::assertNotContains($published->_real()->getId()->toRfc4122(), $ids);
        self::assertContains($uncompleted->_real()->getId()->toRfc4122(), $ids);
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
        self::assertNotContains($unpublished->_real()->getId()->toRfc4122(), $ids);
    }

    public function testGetRecentDossiersWithDepartmentFilter(): void
    {
        $department = DepartmentFactory::random();

        AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'departments' => [$department]]);
        $publishedForDepartment = CovenantFactory::createOne(['status' => DossierStatus::PUBLISHED, 'departments' => [$department]]);
        CovenantFactory::createOne(['status' => DossierStatus::PUBLISHED, 'departments' => []]);

        $result = $this->repository->getRecentDossiers(4, $department->_real());
        $ids = array_map(
            static fn (AbstractDossier $dossier) => $dossier->getId()->toRfc4122(),
            $result,
        );

        self::assertEquals([$publishedForDepartment->_real()->getId()->toRfc4122()], $ids);
    }
}
