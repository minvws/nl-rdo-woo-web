<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DossierUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;

class DossierUploadStatusTest extends UnitTestCase
{
    private DossierUploadStatus $dossierUploadStatus;
    private Document&MockInterface $missingUpload;
    private Document&MockInterface $completedUpload;
    private Document&MockInterface $unwantedUpload;
    private WooDecision&MockInterface $dossier;

    protected function setUp(): void
    {
        $this->missingUpload = Mockery::mock(Document::class);
        $this->completedUpload = Mockery::mock(Document::class);
        $this->unwantedUpload = Mockery::mock(Document::class);

        $this->dossier = Mockery::mock(WooDecision::class);

        $this->dossierUploadStatus = new DossierUploadStatus($this->dossier);

        parent::setUp();
    }

    public function testGetExpectedDocuments(): void
    {
        $this->missingUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            new ArrayCollection([
                $this->missingUpload,
                $this->completedUpload,
            ]),
            $this->dossierUploadStatus->getExpectedDocuments(),
        );
    }

    public function testGetUploadedDocuments(): void
    {
        $this->missingUpload->expects('isUploaded')->andReturnFalse();
        $this->completedUpload->expects('isUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('isUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            [$this->completedUpload],
            $this->dossierUploadStatus->getUploadedDocuments()->getValues(),
        );
    }

    public function testGetExpectedUploadCount(): void
    {
        $this->missingUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            2,
            $this->dossierUploadStatus->getExpectedUploadCount(),
        );
    }

    public function testGetActualUploadCount(): void
    {
        $this->missingUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->missingUpload->expects('isUploaded')->andReturnFalse();
        $this->completedUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->expects('isUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertEquals(
            1,
            $this->dossierUploadStatus->getActualUploadCount(),
        );
    }

    public function testIsCompleteReturnsFalseWithMissingUpload(): void
    {
        $this->missingUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->missingUpload->expects('isUploaded')->andReturnFalse();
        $this->completedUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->expects('isUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertFalse(
            $this->dossierUploadStatus->isComplete(),
        );
    }

    public function testIsCompleteReturnsTrueWhenAllExpectedUploadsAreDone(): void
    {
        $this->completedUpload->expects('shouldBeUploaded')->andReturnTrue();
        $this->completedUpload->expects('isUploaded')->andReturnTrue();
        $this->unwantedUpload->expects('shouldBeUploaded')->andReturnFalse();

        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        self::assertTrue(
            $this->dossierUploadStatus->isComplete(),
        );
    }

    public function testGetDocumentsToUpload(): void
    {
        $this->missingUpload->expects('shouldBeUploaded')->times(2)->andReturnTrue();
        $this->missingUpload->expects('isUploaded')->times(2)->andReturnFalse();

        $this->completedUpload->expects('shouldBeUploaded')->times(2)->andReturnTrue();
        $this->completedUpload->expects('isUploaded')->times(2)->andReturnTrue();

        $this->unwantedUpload->expects('shouldBeUploaded')->times(2)->andReturnFalse();

        $this->dossier->expects('getDocuments')->times(2)->andReturn(new ArrayCollection([
            $this->missingUpload,
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        $documentId = DocumentId::create('123');
        $this->missingUpload->expects('getDocumentId')->times(4)->andReturn($documentId);

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
        $document1 = Mockery::mock(Document::class);
        $document1->expects('shouldBeUploaded')->andReturn(false);

        $document2 = Mockery::mock(Document::class);
        $document2->expects('getDocumentId')->andReturn(DocumentId::create('1002'));
        $document2->expects('shouldBeUploaded')->andReturn(true);
        $document2->expects('isUploaded')->andReturn(false);

        $document3 = Mockery::mock(Document::class);
        $document3->expects('shouldBeUploaded')->andReturn(true);
        $document3->expects('isUploaded')->andReturn(true);

        $document4 = Mockery::mock(Document::class);
        $document4->expects('getDocumentId')->andReturn(DocumentId::create('1004'));
        $document4->expects('shouldBeUploaded')->andReturn(true);
        $document4->expects('isUploaded')->andReturn(false);

        $document5 = Mockery::mock(Document::class);
        $document5->expects('getDocumentId')->andReturn(DocumentId::create('1005'));
        $document5->expects('shouldBeUploaded')->andReturn(true);
        $document5->expects('isUploaded')->andReturn(false);

        $documents = new ArrayCollection([
            $document1,
            $document2,
            $document3,
            $document4,
            $document5,
        ]);

        $this->dossier->expects('getDocuments')->andReturn($documents);

        self::assertEquals(
            [
                '1002',
                '1004',
                '1005',
            ],
            $this->dossierUploadStatus->getMissingDocuments()
                ->map(static fn (Document $document): string => (string) $document->getDocumentId())
                ->getValues(),
        );
    }

    public function testGetMissingDocumentIds(): void
    {
        $document1 = Mockery::mock(Document::class);
        $document1->expects('shouldBeUploaded')->andReturn(false);

        $document2 = Mockery::mock(Document::class);
        $document2->expects('getDocumentId')->andReturn(DocumentId::create('1002'));
        $document2->expects('shouldBeUploaded')->andReturn(true);
        $document2->expects('isUploaded')->andReturn(false);

        $document3 = Mockery::mock(Document::class);
        $document3->expects('shouldBeUploaded')->andReturn(true);
        $document3->expects('isUploaded')->andReturn(true);

        $document4 = Mockery::mock(Document::class);
        $document4->expects('getDocumentId')->andReturn(DocumentId::create('1004'));
        $document4->expects('shouldBeUploaded')->andReturn(true);
        $document4->expects('isUploaded')->andReturn(false);

        $document5 = Mockery::mock(Document::class);
        $document5->expects('getDocumentId')->andReturn(DocumentId::create('1005'));
        $document5->expects('shouldBeUploaded')->andReturn(true);
        $document5->expects('isUploaded')->andReturn(false);

        $documents = new ArrayCollection([
            $document1,
            $document2,
            $document3,
            $document4,
            $document5,
        ]);

        $this->dossier->expects('getDocuments')->andReturn($documents);

        self::assertEquals(
            [
                '1002',
                '1004',
                '1005',
            ],
            $this->dossierUploadStatus->getMissingDocumentIds()->getValues(),
        );
    }
}
