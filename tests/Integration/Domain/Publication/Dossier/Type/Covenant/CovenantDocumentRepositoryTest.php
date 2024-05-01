<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $covenant = CovenantFactory::createOne();

        $covenantDocument = CovenantDocumentFactory::createOne([
            'dossier' => $covenant,
        ]);

        /** @var CovenantDocumentRepository $repository */
        $repository = self::getContainer()->get(CovenantDocumentRepository::class);

        $result = $repository->findForDossierPrefixAndNr(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
        );

        self::assertNotNull($result);
        self::assertEquals($covenantDocument->getId(), $result->getId());
    }

    public function testFindForDossierPrefixAndNrMismatch(): void
    {
        /** @var CovenantDocumentRepository $repository */
        $repository = self::getContainer()->get(CovenantDocumentRepository::class);

        $result = $repository->findForDossierPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
        );

        self::assertNull($result);
    }
}
