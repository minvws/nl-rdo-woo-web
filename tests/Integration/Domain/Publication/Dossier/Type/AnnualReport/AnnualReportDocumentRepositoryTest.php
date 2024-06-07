<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocumentRepository;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AnnualReportDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AnnualReportDocumentRepository
    {
        /** @var AnnualReportDocumentRepository */
        return self::getContainer()->get(AnnualReportDocumentRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = AnnualReportFactory::createOne();
        AnnualReportDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $repository = $this->getRepository();

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNotNull($result);

        $repository->remove($result, true);

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNull($result);
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $dossier = AnnualReportFactory::createOne();

        $document = AnnualReportDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );

        self::assertNotNull($result);
        self::assertEquals($document->getId(), $result->getId());
    }

    public function testFindForDossierPrefixAndNrMismatch(): void
    {
        $result = $this->getRepository()->findForDossierPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
        );

        self::assertNull($result);
    }

    public function testFindOneByDossierId(): void
    {
        $dossier = AnnualReportFactory::createOne();

        $document = AnnualReportDocumentFactory::createOne([
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
