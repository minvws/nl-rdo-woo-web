<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DossierUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class DossierUploadStatusTest extends UnitTestCase
{
    private DossierUploadStatus $dossierUploadStatus;
    private Document&MockInterface $missingUpload;
    private Document&MockInterface $completedUpload;
    private Document&MockInterface $unwantedUpload;
    private WooDecision&MockInterface $dossier;

    protected function setUp(): void
    {
        $this->missingUpload = \Mockery::mock(Document::class);
        $this->missingUpload->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $this->missingUpload->shouldReceive('isUploaded')->andReturnFalse();

        $this->completedUpload = \Mockery::mock(Document::class);
        $this->completedUpload->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->shouldReceive('isUploaded')->andReturnTrue();

        $this->unwantedUpload = \Mockery::mock(Document::class);
        $this->unwantedUpload->shouldReceive('shouldBeUploaded')->andReturnFalse();
        $this->unwantedUpload->shouldReceive('isUploaded')->andReturnFalse();

        $this->dossier = \Mockery::mock(WooDecision::class);

        $this->dossierUploadStatus = new DossierUploadStatus($this->dossier);

        parent::setUp();
    }

    public function testGetExpectedDocuments(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            new ArrayCollection([
                $this->missingUpload,
                $this->completedUpload,
            ]),
            $this->dossierUploadStatus->getExpectedDocuments()
        );
    }

    public function testGetUploadedDocuments(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEqualsCanonicalizing(
            [
                $this->completedUpload,
            ],
            $this->dossierUploadStatus->getUploadedDocuments()->toArray()
        );
    }

    public function testGetExpectedUploadCount(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            2,
            $this->dossierUploadStatus->getExpectedUploadCount()
        );
    }

    public function testGetActualUploadCount(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            1,
            $this->dossierUploadStatus->getActualUploadCount()
        );
    }

    public function testIsCompleteReturnsFalseWithMissingUpload(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertFalse(
            $this->dossierUploadStatus->isComplete()
        );
    }

    public function testIsCompleteReturnsTrueWhenAllExpectedUploadsAreDone(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertTrue(
            $this->dossierUploadStatus->isComplete()
        );
    }

    public function testGetDocumentsToUpload(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        $this->missingUpload->shouldReceive('getDocumentId')->andReturn(123);

        self::assertEquals(
            [$this->missingUpload],
            $this->dossierUploadStatus->getDocumentsToUpload(['456'])->toArray(),
        );

        self::assertEquals(
            [],
            $this->dossierUploadStatus->getDocumentsToUpload(['123'])->toArray(),
        );
    }

    public function testGetMissingDocuments(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn($this->getDocuments());

        self::assertEquals(
            [
                '1002',
                '1004',
                '1005',
            ],
            $this->dossierUploadStatus->getMissingDocuments()
                ->map(fn (Document $document): string => (string) $document->getDocumentId())
                ->getValues(),
        );
    }

    public function testGetMissingDocumentIds(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn($this->getDocuments());

        self::assertEquals(
            [
                '1002',
                '1004',
                '1005',
            ],
            $this->dossierUploadStatus->getMissingDocumentIds()->getValues(),
        );
    }

    /**
     * @return ArrayCollection<array-key,Document&MockInterface>
     */
    private function getDocuments(): ArrayCollection
    {
        return new ArrayCollection([
            $this->getDocument(documentId: '1001', shouldBeUploaded: false, isUploaded: false),
            $this->getDocument(documentId: '1002', shouldBeUploaded: true, isUploaded: false),
            $this->getDocument(documentId: '1003', shouldBeUploaded: true, isUploaded: true),
            $this->getDocument(documentId: '1004', shouldBeUploaded: true, isUploaded: false),
            $this->getDocument(documentId: '1005', shouldBeUploaded: true, isUploaded: false),
        ]);
    }

    private function getDocument(string $documentId, bool $shouldBeUploaded, bool $isUploaded): Document&MockInterface
    {
        /** @var Document&MockInterface $document */
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDocumentId')->andReturn($documentId);
        $document->shouldReceive('shouldBeUploaded')->andReturn($shouldBeUploaded);
        $document->shouldReceive('isUploaded')->andReturn($isUploaded);

        return $document;
    }
}
