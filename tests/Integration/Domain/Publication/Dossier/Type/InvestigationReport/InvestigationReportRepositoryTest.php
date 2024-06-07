<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InvestigationReportRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): InvestigationReportRepository
    {
        /** @var InvestigationReportRepository */
        return self::getContainer()->get(InvestigationReportRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testFindAndRemove(): void
    {
        $dossier = InvestigationReportFactory::createOne();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $repository->remove($result, true);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = InvestigationReportFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel($dossier->getDocumentPrefix(), $dossier->getDossierNr());
        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
