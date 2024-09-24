<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ComplaintJudgementRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): ComplaintJudgementRepository
    {
        /** @var ComplaintJudgementRepository */
        return self::getContainer()->get(ComplaintJudgementRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testFindAndRemove(): void
    {
        $dossier = ComplaintJudgementFactory::createOne();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $repository->remove($result, true);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = ComplaintJudgementFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel($dossier->getDocumentPrefix(), $dossier->getDossierNr());
        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
