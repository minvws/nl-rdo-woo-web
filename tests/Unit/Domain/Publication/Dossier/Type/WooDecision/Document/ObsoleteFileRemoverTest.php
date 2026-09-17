<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ObsoleteFileRemover;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;

final class ObsoleteFileRemoverTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbStorage;
    private ObsoleteFileRemover $obsoleteFileRemover;

    protected function setUp(): void
    {
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->thumbStorage = Mockery::mock(ThumbnailStorageService::class);

        $this->obsoleteFileRemover = new ObsoleteFileRemover(
            $this->entityStorageService,
            $this->thumbStorage,
        );

        parent::setUp();
    }

    public function testKeepsTheUploadWhenTheDocumentStillExpectsOne(): void
    {
        $this->entityStorageService->expects('deleteAllFilesForEntity')->never();
        $this->thumbStorage->expects('deleteAllThumbsForEntity')->never();

        $document = $this->createUploadedDocument();

        $this->obsoleteFileRemover->removeIfObsolete($document);

        $fileInfo = $document->getFileInfo();
        self::assertTrue($fileInfo->isUploaded());
        self::assertSame('hash-of-original', $fileInfo->getHash());
        self::assertSame('/path/to/original.pdf', $fileInfo->getPath());
        self::assertSame(1234, $fileInfo->getSize());
    }

    public function testRemovesTheUploadWhenTheJudgementIsNoLongerPublic(): void
    {
        $document = $this->createUploadedDocument();
        $document->setJudgement(Judgement::NOT_PUBLIC);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbStorage->expects('deleteAllThumbsForEntity')->with($document);

        $this->obsoleteFileRemover->removeIfObsolete($document);

        $fileInfo = $document->getFileInfo();
        self::assertFalse($fileInfo->isUploaded());
        self::assertNull($fileInfo->getHash());
        self::assertNull($fileInfo->getPath());
        self::assertSame(0, $fileInfo->getSize());
    }

    public function testRemovesTheUploadWhenTheDocumentIsSuspended(): void
    {
        $document = $this->createUploadedDocument();
        $document->setSuspended(true);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbStorage->expects('deleteAllThumbsForEntity')->with($document);

        $this->obsoleteFileRemover->removeIfObsolete($document);

        self::assertFalse($document->getFileInfo()->isUploaded());
    }

    private function createUploadedDocument(): Document
    {
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);

        $fileInfo = $document->getFileInfo();
        $fileInfo->setName('original.pdf');
        $fileInfo->setUploaded(true);
        $fileInfo->setHash('hash-of-original');
        $fileInfo->setPath('/path/to/original.pdf');
        $fileInfo->setSize(1234);

        return $document;
    }
}
