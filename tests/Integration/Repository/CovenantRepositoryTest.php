<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\CovenantRepository;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use App\ViewModel\CovenantSearchEntry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $covenant = CovenantFactory::createOne();
        CovenantAttachmentFactory::createMany(2, [
            'dossier' => $covenant,
        ]);

        /** @var CovenantRepository $repository */
        $repository = self::getContainer()->get(CovenantRepository::class);

        $result = $repository->getSearchEntry(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
        );

        self::assertInstanceOf(CovenantSearchEntry::class, $result);
        self::assertEquals(3, $result->documentCount); // 2 attachments + 1 main document = 3
    }
}
