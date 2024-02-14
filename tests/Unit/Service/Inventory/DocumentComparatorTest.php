<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\Inventory\DocumentComparator;
use App\Service\Inventory\DocumentMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentComparatorTest extends MockeryTestCase
{
    private DocumentComparator $documentComparator;
    private Dossier&MockInterface $dossier;
    private DocumentRepository&MockInterface $repository;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(Dossier::class);
        $this->dossier->shouldReceive('getDocumentPrefix')->andReturn('prefix');

        $this->repository = \Mockery::mock(DocumentRepository::class);

        $this->documentComparator = new DocumentComparator(
            $this->repository,
        );

        parent::setUp();
    }

    public function testHasRefersToUpdateReturnsFalseWhenDocumentAndMetadataHaveNoReferrals(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());

        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn([]);

        $this->assertFalse(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata)
        );
    }

    public function testHasRefersToUpdateReturnsTrueWhenAReferralIsAdded(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());
        $document->shouldReceive('getDocumentNr')->andReturn('bar-123');
        $document->shouldReceive('getDocumentId')->andReturn('123');

        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn(['foo-123']);

        $referredDocument = \Mockery::mock(Document::class);
        $referredDocument->shouldReceive('getDocumentNr')->andReturn('foo-123');
        $referredDocument->shouldReceive('getDocumentId')->andReturn('123');

        $this->repository->expects('findByDocumentNumber')->andReturn($referredDocument);

        $this->assertTrue(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata)
        );
    }

    public function testHasRefersToUpdateIgnoresInvalidReferral(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());
        $document->shouldReceive('getDocumentNr')->andReturn('bar-123');
        $document->shouldReceive('getDocumentId')->andReturn('123');

        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn(['foo-123', 'invalid-456']);

        $referredDocument = \Mockery::mock(Document::class);
        $referredDocument->shouldReceive('getDocumentNr')->andReturn('foo-123');
        $referredDocument->shouldReceive('getDocumentId')->andReturn('123');

        $this->repository->expects('findByDocumentNumber')->andReturn($referredDocument);
        $this->repository->expects('findByDocumentNumber')->andReturnNull();

        $this->assertTrue(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata)
        );
    }
}
