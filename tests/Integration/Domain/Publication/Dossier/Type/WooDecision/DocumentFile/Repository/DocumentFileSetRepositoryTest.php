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

final class DocumentFileSetRepositoryTest extends SharedWebTestCase
{
    public function testSaveAndRemove(): void
    {
        $dossier = WooDecisionFactory::createOne();
        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSetRepository = self::fromContainer(DocumentFileSetRepository::class);

        $documentFileSetRepository->save($documentFileSet, true);
        $result = $documentFileSetRepository->find($documentFileSet->getId());
        self::assertEquals($documentFileSet, $result);

        $documentFileSetRepository->remove($documentFileSet, true);
        $result = $documentFileSetRepository->find($documentFileSet->getId());
        self::assertNull($result);
    }

    public function testFindUncompletedByDossier(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();
        $documentFileSetRepository = self::fromContainer(DocumentFileSetRepository::class);

        $documentFileSetA = new DocumentFileSet($dossierA);
        $documentFileSetRepository->save($documentFileSetA, true);

        $documentFileSetACompleted = new DocumentFileSet($dossierA);
        $documentFileSetACompleted->setStatus(DocumentFileSetStatus::COMPLETED);
        $documentFileSetRepository->save($documentFileSetACompleted, true);

        $documentFileSetB = new DocumentFileSet($dossierB);
        $documentFileSetRepository->save($documentFileSetB, true);

        self::assertEquals($documentFileSetA, $documentFileSetRepository->findUncompletedByDossier($dossierA));
    }

    public function testFindUncompletedByDossierReturnsNullWhenNoMatchIsFound(): void
    {
        $dossier = WooDecisionFactory::createOne();
        $documentFileSetRepository = self::fromContainer(DocumentFileSetRepository::class);

        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);
        $documentFileSetRepository->save($documentFileSet, true);

        self::assertNull($documentFileSetRepository->findUncompletedByDossier($dossier));
    }

    public function testCountUploadsToProcess(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne();
        $documentFileSetRepository = self::fromContainer(DocumentFileSetRepository::class);

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

        self::assertEquals(2, $documentFileSetRepository->countUploadsToProcess($documentFileSet));
    }

    public function testCountUpdatesToProcess(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne();
        $documentFileSetRepository = self::fromContainer(DocumentFileSetRepository::class);

        DocumentFileUpdateFactory::createOne([
            'status' => DocumentFileUpdateStatus::COMPLETED,
            'documentFileSet' => $documentFileSet,
        ]);
        DocumentFileUpdateFactory::createOne([
            'status' => DocumentFileUpdateStatus::PENDING,
            'documentFileSet' => $documentFileSet,
        ]);

        self::assertEquals(1, $documentFileSetRepository->countUpdatesToProcess($documentFileSet));
    }
}
