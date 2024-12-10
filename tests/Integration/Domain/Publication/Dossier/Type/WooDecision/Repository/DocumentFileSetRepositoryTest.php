<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

final class DocumentFileSetRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private DocumentFileSetRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(DocumentFileSetRepository::class);
        Assert::isInstanceOf($repository, DocumentFileSetRepository::class);

        $this->repository = $repository;
    }

    public function testSave(): void
    {
        $dossier = WooDecisionFactory::createOne()->_real();
        $documentFileSet = new DocumentFileSet($dossier);

        $this->repository->save($documentFileSet, true);
        $result = $this->repository->find($documentFileSet->getId());
        self::assertEquals($documentFileSet, $result);
    }

    public function testFindUncompletedByDossier(): void
    {
        $dossierA = WooDecisionFactory::createOne()->_real();
        $dossierB = WooDecisionFactory::createOne()->_real();

        $documentFileSetA = new DocumentFileSet($dossierA);
        $this->repository->save($documentFileSetA, true);

        $documentFileSetACompleted = new DocumentFileSet($dossierA);
        $documentFileSetACompleted->setStatus(DocumentFileSetStatus::COMPLETED);
        $this->repository->save($documentFileSetACompleted, true);

        $documentFileSetB = new DocumentFileSet($dossierB);
        $this->repository->save($documentFileSetB, true);

        self::assertEquals(
            $documentFileSetA,
            $this->repository->findUncompletedByDossier($dossierA),
        );
    }

    public function testFindUncompletedByDossierReturnsNullWhenNoMatchIsFound(): void
    {
        $dossier = WooDecisionFactory::createOne()->_real();

        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);
        $this->repository->save($documentFileSet, true);

        self::assertNull(
            $this->repository->findUncompletedByDossier($dossier),
        );
    }
}
