<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DispositionRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): DispositionRepository
    {
        /** @var DispositionRepository */
        return self::getContainer()->get(DispositionRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testFindAndRemove(): void
    {
        $dossier = DispositionFactory::createOne();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $repository->remove($result, true);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = DispositionFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel($dossier->getDocumentPrefix(), $dossier->getDossierNr());
        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
