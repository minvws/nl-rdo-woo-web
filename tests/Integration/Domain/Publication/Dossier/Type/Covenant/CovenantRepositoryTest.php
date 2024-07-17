<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use App\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): CovenantRepository
    {
        /** @var CovenantRepository */
        return self::getContainer()->get(CovenantRepository::class);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testSaveAndRemove(): void
    {
        $dossier = new Covenant();

        $repository = $this->getRepository();

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());

        $repository->save($dossier, true);

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier, $result);

        $repository->remove($dossier, true);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }

    public function testGetSearchResultViewModel(): void
    {
        $covenant = CovenantFactory::createOne();
        CovenantAttachmentFactory::createMany(2, [
            'dossier' => $covenant,
        ]);
        $repository = $this->getRepository();

        $result = $repository->getSearchResultViewModel(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
        );

        self::assertInstanceOf(CovenantSearchResult::class, $result);
        self::assertEquals(3, $result->documentCount); // 2 attachments + 1 main document = 3
    }
}
