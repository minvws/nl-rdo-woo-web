<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocumentRepository;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class InvestigationReportDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): InvestigationReportDocumentRepository
    {
        /** @var InvestigationReportDocumentRepository */
        return self::getContainer()->get(InvestigationReportDocumentRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = InvestigationReportFactory::createOne();
        InvestigationReportDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $repository = $this->getRepository();

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNotNull($result);

        $repository->remove($result, true);

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNull($result);
    }

    public function testFindForDossierByPrefixAndNrFindsMatch(): void
    {
        $dossier = InvestigationReportFactory::createOne();

        $document = InvestigationReportDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );

        self::assertNotNull($result);
        self::assertEquals($document->getId(), $result->getId());
    }

    public function testFindForDossierByPrefixAndNrMismatch(): void
    {
        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
        );

        self::assertNull($result);
    }

    public function testFindOneByDossierId(): void
    {
        $dossier = InvestigationReportFactory::createOne();

        $document = InvestigationReportDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        self::assertEquals(
            $document->getId(),
            $this->getRepository()->findOneByDossierId($dossier->getId())?->getId(),
        );

        self::assertNull(
            $this->getRepository()->findOneByDossierId(Uuid::v6())
        );
    }
}
