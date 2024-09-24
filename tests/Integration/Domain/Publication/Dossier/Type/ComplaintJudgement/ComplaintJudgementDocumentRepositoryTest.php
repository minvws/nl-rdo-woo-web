<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocumentRepository;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ComplaintJudgementDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): ComplaintJudgementDocumentRepository
    {
        /** @var ComplaintJudgementDocumentRepository */
        return self::getContainer()->get(ComplaintJudgementDocumentRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = ComplaintJudgementFactory::createOne();
        ComplaintJudgementDocumentFactory::createOne([
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
        $dossier = ComplaintJudgementFactory::createOne();

        $document = ComplaintJudgementDocumentFactory::createOne([
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
        $dossier = ComplaintJudgementFactory::createOne();

        $document = ComplaintJudgementDocumentFactory::createOne([
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
