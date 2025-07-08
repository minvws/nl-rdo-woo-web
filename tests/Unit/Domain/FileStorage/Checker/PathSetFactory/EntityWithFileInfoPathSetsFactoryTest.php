<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker\PathSetFactory;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Domain\FileStorage\Checker\PathSetFactory\EntityWithFileInfoPathSetsFactory;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\FileInfo;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Storage\ThumbnailStorageService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class EntityWithFileInfoPathSetsFactoryTest extends UnitTestCase
{
    private EntityWithFileInfoPathSetsFactory $factory;
    private EntityManagerInterface&MockInterface $entityManager;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private int $thumbnailLimit = 2;

    public function setUp(): void
    {
        $this->factory = new EntityWithFileInfoPathSetsFactory(
            $this->entityManager = \Mockery::mock(EntityManagerInterface::class),
            $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class),
            $this->thumbnailLimit,
        );
    }

    public function testGetPathSets(): void
    {
        $this->entityManager->expects('getRepository')
            ->with(Document::class)
            ->andReturn($documentRepository = \Mockery::mock(DocumentRepository::class));

        $documentRepository->expects('createQueryBuilder->getQuery->toIterable')->andReturn([
            $docA = \Mockery::mock(Document::class),
            $docB = \Mockery::mock(Document::class),
            $docC = \Mockery::mock(Document::class),
        ]);

        $fileInfoA = new FileInfo();
        $fileInfoA->setUploaded(true);
        $fileInfoA->setPath('/doc/a');
        $fileInfoA->setPageCount(3);

        $docA->shouldReceive('getFileInfo')->andReturn($fileInfoA);
        $docA->shouldReceive('getId')->andReturn($uuidA = Uuid::v6());

        $this->thumbnailStorageService->expects('generateThumbPath')->with($docA, 1)->andReturn('/doc/a/thumb/1');
        $this->thumbnailStorageService->expects('generateThumbPath')->with($docA, 2)->andReturn('/doc/a/thumb/2');

        $docB->shouldReceive('getFileInfo->getPath')->andReturnNull();

        $fileInfoC = new FileInfo();
        $fileInfoC->setUploaded(true);
        $fileInfoC->setPath('/doc/c');
        $fileInfoC->setPageCount(0);

        $docC->shouldReceive('getFileInfo')->andReturn($fileInfoC);
        $docC->shouldReceive('getId')->andReturn($uuidC = Uuid::v6());

        $this->entityManager->expects('detach')->with($docA);
        $this->entityManager->expects('detach')->with($docB);
        $this->entityManager->expects('detach')->with($docC);

        $this->setUpExpectationsForEmptyRepo(DocumentFileUpload::class);
        $this->setUpExpectationsForEmptyRepo(DocumentFileUpdate::class);
        $this->setUpExpectationsForEmptyRepo(AbstractAttachment::class);
        $this->setUpExpectationsForEmptyRepo(AbstractMainDocument::class);
        $this->setUpExpectationsForEmptyRepo(Inventory::class);
        $this->setUpExpectationsForEmptyRepo(InquiryInventory::class);
        $this->setUpExpectationsForEmptyRepo(ProductionReport::class);
        $this->setUpExpectationsForEmptyRepo(ProductionReportProcessRun::class);

        self::assertEquals(
            [
                new PathSet(
                    'Document',
                    FileStorageType::DOCUMENT,
                    [
                        '/doc/a' => $uuidA->toRfc4122(),
                        '/doc/c' => $uuidC->toRfc4122(),
                    ],
                ),
                new PathSet(
                    'DocumentThumb',
                    FileStorageType::DOCUMENT,
                    [
                        '/doc/a/thumb/1' => $uuidA->toRfc4122(),
                        '/doc/a/thumb/2' => $uuidA->toRfc4122(),
                    ],
                ),
            ],
            iterator_to_array($this->factory->getPathSets(), false),
        );
    }

    private function setUpExpectationsForEmptyRepo(string $entityClass): void
    {
        $this->entityManager
            ->expects('getRepository')
            ->with($entityClass)
            ->andReturn($repository = \Mockery::mock(ServiceEntityRepository::class));

        $repository->expects('createQueryBuilder->getQuery->toIterable')->andReturn([]);
    }
}
