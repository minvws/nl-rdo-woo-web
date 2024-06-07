<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbstractDossierRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AbstractDossierRepository
    {
        /** @var AbstractDossierRepository */
        return self::getContainer()->get(AbstractDossierRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = CovenantFactory::createOne();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $repository->remove($result);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }

    public function testFindBySearchTerm(): void
    {
        $covenant = CovenantFactory::createOne();
        $repository = $this->getRepository();

        $result = $repository->findBySearchTerm(
            $covenant->getDossierNr(),
            10,
            $covenant->getOrganisation(),
        );

        self::assertCount(1, $result);
    }

    public function testFindDossiersPendingPublication(): void
    {
        $covenant = CovenantFactory::createOne(['status' => DossierStatus::SCHEDULED, 'completed' => true]);
        $wooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW, 'completed' => true]);
        $annualReport = AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'completed' => true]);
        $published = AnnualReportFactory::createOne(['status' => DossierStatus::PUBLISHED, 'completed' => false]);
        $uncompleted = AnnualReportFactory::createOne(['status' => DossierStatus::CONCEPT, 'completed' => false]);

        $result = $this->getRepository()->findDossiersPendingPublication();
        $ids = array_map(
            static fn (AbstractDossier $dossier) => $dossier->getId()->toRfc4122(),
            $result,
        );

        self::assertContains($covenant->getId()->toRfc4122(), $ids);
        self::assertContains($wooDecision->getId()->toRfc4122(), $ids);
        self::assertContains($annualReport->getId()->toRfc4122(), $ids);
        self::assertNotContains($published->getId()->toRfc4122(), $ids);
        self::assertNotContains($uncompleted->getId()->toRfc4122(), $ids);
    }
}
