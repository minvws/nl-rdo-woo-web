<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUploadFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Webmozart\Assert\Assert;

final class DocumentFileSetRepositoryTest extends SharedWebTestCase
{
    private DocumentFileSetRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(DocumentFileSetRepository::class);
        Assert::isInstanceOf($repository, DocumentFileSetRepository::class);

        $this->repository = $repository;
    }

    public function testSaveAndRemove(): void
    {
        $dossier = WooDecisionFactory::createOne();
        $documentFileSet = new DocumentFileSet($dossier);

        $this->repository->save($documentFileSet, true);
        $result = $this->repository->find($documentFileSet->getId());
        self::assertEquals($documentFileSet, $result);

        $this->repository->remove($documentFileSet, true);
        $result = $this->repository->find($documentFileSet->getId());
        self::assertNull($result);
    }

    public function testFindUncompletedByDossier(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();

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
        $dossier = WooDecisionFactory::createOne();

        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);
        $this->repository->save($documentFileSet, true);

        self::assertNull(
            $this->repository->findUncompletedByDossier($dossier),
        );
    }

    public function testCountUploadsToProcess(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne();

        DocumentFileUploadFactory::createOne([
            'status' => DocumentFileUploadStatus::PROCESSED,
            'documentFileSet' => $documentFileSet,
        ]);
        DocumentFileUploadFactory::createOne([
            'status' => DocumentFileUploadStatus::FAILED,
            'documentFileSet' => $documentFileSet,
        ]);
        DocumentFileUploadFactory::createOne([
            'status' => DocumentFileUploadStatus::PENDING,
            'documentFileSet' => $documentFileSet,
        ]);
        DocumentFileUploadFactory::createOne([
            'status' => DocumentFileUploadStatus::UPLOADED,
            'documentFileSet' => $documentFileSet,
        ]);

        self::assertEquals(
            2,
            $this->repository->countUploadsToProcess($documentFileSet),
        );
    }

    public function testCountUpdatesToProcess(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne();

        DocumentFileUpdateFactory::createOne([
            'status' => DocumentFileUpdateStatus::COMPLETED,
            'documentFileSet' => $documentFileSet,
        ]);
        DocumentFileUpdateFactory::createOne([
            'status' => DocumentFileUpdateStatus::PENDING,
            'documentFileSet' => $documentFileSet,
        ]);

        self::assertEquals(
            1,
            $this->repository->countUpdatesToProcess($documentFileSet),
        );
    }
}
