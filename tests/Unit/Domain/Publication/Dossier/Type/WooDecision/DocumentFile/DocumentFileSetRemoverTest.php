<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetRemover;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;

class DocumentFileSetRemoverTest extends UnitTestCase
{
    private DocumentFileSetRepository&MockInterface $documentFileSetRepository;
    private EntityStorageService&MockInterface $entityStorageService;
    private DocumentFileSetRemover $service;

    public function setUp(): void
    {
        $this->documentFileSetRepository = \Mockery::mock(DocumentFileSetRepository::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);

        $this->service = new DocumentFileSetRemover(
            $this->documentFileSetRepository,
            $this->entityStorageService,
        );
    }

    public function testRemoveAllFinalSets(): void
    {
        // Set A has 2 uploads and 2 updates
        $uploadA1 = \Mockery::mock(DocumentFileUpload::class);
        $uploadA2 = \Mockery::mock(DocumentFileUpload::class);
        $updateA1 = \Mockery::mock(DocumentFileUpdate::class);
        $updateA2 = \Mockery::mock(DocumentFileUpdate::class);

        $setA = \Mockery::mock(DocumentFileSet::class);
        $setA->shouldReceive('getUploads')->andReturn(new ArrayCollection([$uploadA1, $uploadA2]));
        $setA->shouldReceive('getUpdates')->andReturn(new ArrayCollection([$updateA1, $updateA2]));

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($uploadA1);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($uploadA2);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($updateA1);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($updateA2);
        $this->documentFileSetRepository->expects('remove')->with($setA, true);

        // Set B has only one upload and no updates
        $uploadB1 = \Mockery::mock(DocumentFileUpload::class);

        $setB = \Mockery::mock(DocumentFileSet::class);
        $setB->shouldReceive('getUploads')->andReturn(new ArrayCollection([$uploadB1]));
        $setB->shouldReceive('getUpdates')->andReturn(new ArrayCollection());

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($uploadB1);
        $this->documentFileSetRepository->expects('remove')->with($setB, true);

        $this->documentFileSetRepository
            ->expects('findAllWithFinalStatus')
            ->andReturn([$setA, $setB]);

        self::assertEquals(2, $this->service->removeAllFinalSets());
    }
}
