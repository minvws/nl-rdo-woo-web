<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentRepository;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AnnualReportAttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AnnualReportAttachmentRepository
    {
        /** @var AnnualReportAttachmentRepository */
        return self::getContainer()->get(AnnualReportAttachmentRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = AnnualReportFactory::createOne();
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $repository = $this->getRepository();

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122(),
        );
        self::assertNotNull($result);

        $repository->remove($result, true);

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122(),
        );
        self::assertNull($result);
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $dossier = AnnualReportFactory::createOne();
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122(),
        );

        self::assertNotNull($result);
        self::assertEquals($attachment->getId(), $result->getId());
    }

    public function testFindForDossierPrefixAndNrMismatch(): void
    {
        $result = $this->getRepository()->findForDossierPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
            $this->getFaker()->uuid(),
        );

        self::assertNull($result);
    }

    public function testFindAllForDossier(): void
    {
        $dossier = AnnualReportFactory::createOne();
        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);
        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findAllForDossier(
            $dossier->getId(),
        );

        self::assertCount(2, $result);
    }

    public function testFindOneForDossier(): void
    {
        $dossier = AnnualReportFactory::createOne();
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findOneForDossier(
            $dossier->getId(),
            $attachment->getId(),
        );

        self::assertEquals($attachment->getId(), $result->getId());

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneForDossier(
            $dossier->getId(),
            Uuid::v6(),
        );
    }

    public function testFindOneOrNullForDossier(): void
    {
        $dossier = AnnualReportFactory::createOne();
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $result = $this->getRepository()->findOneOrNullForDossier(
            $dossier->getId(),
            $attachment->getId(),
        );

        self::assertNotNull($result);
        self::assertEquals($attachment->getId(), $result->getId());

        self::assertNull(
            $this->getRepository()->findOneOrNullForDossier(
                $dossier->getId(),
                Uuid::v6(),
            )
        );
    }
}
