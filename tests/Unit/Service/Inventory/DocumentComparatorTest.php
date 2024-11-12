<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Judgement;
use App\Repository\DocumentRepository;
use App\Service\Inventory\DocumentComparator;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\DocumentNumber;
use App\Service\Inventory\MetadataField;
use App\SourceType;
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

    public function testDocumentCompare(): void
    {
        $documentNr = \Mockery::mock(DocumentNumber::class);

        $document = \Mockery::mock(Document::class);
        $document->expects('getJudgement')->twice()->andReturn(Judgement::NOT_PUBLIC);
        $document->expects('getFamilyId')->twice()->andReturn(1);
        $document->expects('getThreadId')->twice()->andReturn(1);
        $document->expects('getGrounds')->twice()->andReturn([]);
        $document->expects('getSubjects')->twice()->andReturn([]);
        $document->expects('getPeriod')->twice()->andReturnNull();
        $document->expects('isSuspended')->twice()->andReturnFalse();
        $document->expects('getLinks')->twice()->andReturn([]);
        $document->expects('getRemark')->twice()->andReturnNull();
        $document->expects('getDocumentDate')->twice()->andReturnNull();
        $document->expects('getFileInfo->getSourceType')->twice()->andReturn(SourceType::EMAIL);
        $document->expects('getFileInfo->getName')->twice()->andReturn('foo.txt');
        $document->expects('getInquiries')->twice()->andReturn(new ArrayCollection());
        $document->expects('getRefersTo')->twice()->andReturn(new ArrayCollection());
        $document->shouldReceive('getDocumentNr')->andReturn($documentNr);

        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getJudgement')->twice()->andReturn(Judgement::ALREADY_PUBLIC);
        $metadata->expects('getFamilyId')->twice()->andReturn(1);
        $metadata->expects('getThreadId')->twice()->andReturn(1);
        $metadata->expects('getGrounds')->twice()->andReturn([]);
        $metadata->expects('getSubjects')->twice()->andReturn([]);
        $metadata->expects('getPeriod')->twice()->andReturnNull();
        $metadata->expects('isSuspended')->twice()->andReturnFalse();
        $metadata->expects('getLinks')->twice()->andReturn([]);
        $metadata->expects('getRemark')->twice()->andReturnNull();
        $metadata->expects('getDate')->twice()->andReturnNull();
        $metadata->expects('getSourceType')->twice()->andReturn(SourceType::EMAIL);
        $metadata->expects('getFilename')->twice()->andReturn('foo.txt');
        $metadata->expects('getCaseNumbers')->twice()->andReturn([]);
        $metadata->expects('getRefersTo')->twice()->andReturn([]);

        $changeset = $this->documentComparator->getChangeset($this->dossier, $document, $metadata);
        self::assertTrue($changeset->hasChanges());
        self::assertTrue($changeset->isChanged(MetadataField::JUDGEMENT->value));
        self::assertFalse($changeset->isChanged(MetadataField::FAMILY->value));

        self::assertTrue($this->documentComparator->needsUpdate($this->dossier, $document, $metadata));
    }

    public function testHasRefersToUpdateReturnsFalseWhenDocumentAndMetadataHaveNoReferrals(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());

        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn([]);

        self::assertFalse(
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

        self::assertTrue(
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

        self::assertTrue(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata)
        );
    }
}
