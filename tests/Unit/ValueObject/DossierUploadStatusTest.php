<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\Entity\Document;
use App\Entity\Dossier;
use App\ValueObject\DossierUploadStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierUploadStatusTest extends MockeryTestCase
{
    private DossierUploadStatus $dossierUploadStatus;
    private Document&MockInterface $missingUpload;
    private Document&MockInterface $completedUpload;
    private Document&MockInterface $unwantedUpload;
    private Dossier&MockInterface $dossier;

    public function setUp(): void
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

        $this->dossier = \Mockery::mock(Dossier::class);

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

        $this->assertEquals(
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

        $this->assertEqualsCanonicalizing(
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

        $this->assertEquals(
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

        $this->assertEquals(
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

        $this->assertFalse(
            $this->dossierUploadStatus->isComplete()
        );
    }

    public function testIsCompleteReturnsTrueWhenAllExpectedUploadsAreDone(): void
    {
        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $this->completedUpload,
            $this->unwantedUpload,
        ]));

        $this->assertTrue(
            $this->dossierUploadStatus->isComplete()
        );
    }
}
