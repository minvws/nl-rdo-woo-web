<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocumentRepository;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DispositionDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): DispositionDocumentRepository
    {
        /** @var DispositionDocumentRepository */
        return self::getContainer()->get(DispositionDocumentRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testRemove(): void
    {
        $dossier = DispositionFactory::createOne();
        DispositionDocumentFactory::createOne([
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
        $dossier = DispositionFactory::createOne();

        $document = DispositionDocumentFactory::createOne([
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
        $dossier = DispositionFactory::createOne();

        $document = DispositionDocumentFactory::createOne([
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
