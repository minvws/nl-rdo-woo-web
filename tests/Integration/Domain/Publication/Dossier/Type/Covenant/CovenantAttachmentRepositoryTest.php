<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantAttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $covenant = CovenantFactory::createOne();

        $covenantAttachment = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
        ]);

        /** @var CovenantAttachmentRepository $repository */
        $repository = self::getContainer()->get(CovenantAttachmentRepository::class);

        $result = $repository->findForDossierPrefixAndNr(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
            $covenantAttachment->getId()->toRfc4122(),
        );

        self::assertNotNull($result);
        self::assertEquals($covenantAttachment->getId(), $result->getId());
    }

    public function testFindForDossierPrefixAndNrMismatch(): void
    {
        /** @var CovenantAttachmentRepository $repository */
        $repository = self::getContainer()->get(CovenantAttachmentRepository::class);

        $result = $repository->findForDossierPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
            $this->getFaker()->uuid(),
        );

        self::assertNull($result);
    }
}
