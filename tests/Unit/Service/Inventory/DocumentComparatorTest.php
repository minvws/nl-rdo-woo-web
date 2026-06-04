<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\CaseNumbers;
use Shared\Service\Inventory\DocumentComparator;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Service\Inventory\MetadataField;
use Shared\Tests\Unit\UnitTestCase;

class DocumentComparatorTest extends UnitTestCase
{
    private DocumentComparator $documentComparator;
    private WooDecision&MockInterface $dossier;
    private DocumentRepository&MockInterface $repository;

    protected function setUp(): void
    {
        $this->dossier = Mockery::mock(WooDecision::class);
        $this->repository = Mockery::mock(DocumentRepository::class);
        $this->documentComparator = new DocumentComparator($this->repository);

        parent::setUp();
    }

    public function testDocumentCompare(): void
    {
        $documentNr = Mockery::mock(DocumentNumber::class);

        $document = Mockery::mock(Document::class);
        $document->expects('getJudgement')->times(2)->andReturn(Judgement::NOT_PUBLIC);
        $document->expects('getFamilyId')->times(2)->andReturn(1);
        $document->expects('getThreadId')->times(2)->andReturn(1);
        $document->expects('getGrounds')->times(2)->andReturn([]);
        $document->expects('getPeriod')->times(2)->andReturnNull();
        $document->expects('isSuspended')->times(2)->andReturnFalse();
        $document->expects('getLinks')->times(2)->andReturn([]);
        $document->expects('getRemark')->times(2)->andReturnNull();
        $document->expects('getDocumentDate')->times(2)->andReturnNull();
        $document->expects('getFileInfo->getSourceType')->times(2)->andReturn(SourceType::EMAIL);
        $document->expects('getFileInfo->getName')->times(2)->andReturn('foo.txt');
        $document->expects('getInquiries')->times(2)->andReturn(new ArrayCollection());
        $document->expects('getRefersTo')->times(2)->andReturn(new ArrayCollection());
        $document->expects('getDocumentNr')->times(2)->andReturn($documentNr);

        $metadata = Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getJudgement')->times(2)->andReturn(Judgement::ALREADY_PUBLIC);
        $metadata->expects('getFamilyId')->times(2)->andReturn(1);
        $metadata->expects('getThreadId')->times(2)->andReturn(1);
        $metadata->expects('getGrounds')->times(2)->andReturn([]);
        $metadata->expects('getPeriod')->times(2)->andReturnNull();
        $metadata->expects('isSuspended')->times(2)->andReturnFalse();
        $metadata->expects('getLinks')->times(2)->andReturn([]);
        $metadata->expects('getRemark')->times(2)->andReturnNull();
        $metadata->expects('getDate')->times(2)->andReturnNull();
        $metadata->expects('getSourceType')->times(2)->andReturn(SourceType::EMAIL);
        $metadata->expects('getFilename')->times(2)->andReturn('foo.txt');
        $metadata->expects('getCaseNumbers')->times(2)->andReturn(CaseNumbers::empty());
        $metadata->expects('getRefersTo')->times(2)->andReturn([]);

        $changeset = $this->documentComparator->getChangeset($this->dossier, $document, $metadata);
        self::assertTrue($changeset->hasChanges());
        self::assertTrue($changeset->isChanged(MetadataField::JUDGEMENT->value));
        self::assertFalse($changeset->isChanged(MetadataField::FAMILY->value));

        self::assertTrue($this->documentComparator->needsUpdate($this->dossier, $document, $metadata));
    }

    public function testDocumentCompareIgnoresCaseNrRemoval(): void
    {
        $documentNr = Mockery::mock(DocumentNumber::class);
        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getCaseNr')->times(2)->andReturn('foo-123');

        $document = Mockery::mock(Document::class);
        $document->expects('getJudgement')->times(2)->andReturn(Judgement::NOT_PUBLIC);
        $document->expects('getFamilyId')->times(2)->andReturn(1);
        $document->expects('getThreadId')->times(2)->andReturn(1);
        $document->expects('getGrounds')->times(2)->andReturn([]);
        $document->expects('getPeriod')->times(2)->andReturnNull();
        $document->expects('isSuspended')->times(2)->andReturnFalse();
        $document->expects('getLinks')->times(2)->andReturn([]);
        $document->expects('getRemark')->times(2)->andReturnNull();
        $document->expects('getDocumentDate')->times(2)->andReturnNull();
        $document->expects('getFileInfo->getSourceType')->times(2)->andReturn(SourceType::EMAIL);
        $document->expects('getFileInfo->getName')->times(2)->andReturn('foo.txt');
        $document->expects('getInquiries')->times(2)->andReturn(new ArrayCollection([$inquiry]));
        $document->expects('getRefersTo')->times(2)->andReturn(new ArrayCollection());
        $document->expects('getDocumentNr')->times(2)->andReturn($documentNr);

        $metadata = Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getJudgement')->times(2)->andReturn(Judgement::NOT_PUBLIC);
        $metadata->expects('getFamilyId')->times(2)->andReturn(1);
        $metadata->expects('getThreadId')->times(2)->andReturn(1);
        $metadata->expects('getGrounds')->times(2)->andReturn([]);
        $metadata->expects('getPeriod')->times(2)->andReturnNull();
        $metadata->expects('isSuspended')->times(2)->andReturnFalse();
        $metadata->expects('getLinks')->times(2)->andReturn([]);
        $metadata->expects('getRemark')->times(2)->andReturnNull();
        $metadata->expects('getDate')->times(2)->andReturnNull();
        $metadata->expects('getSourceType')->times(2)->andReturn(SourceType::EMAIL);
        $metadata->expects('getFilename')->times(2)->andReturn('foo.txt');
        $metadata->expects('getCaseNumbers')->times(2)->andReturn(CaseNumbers::empty());
        $metadata->expects('getRefersTo')->times(2)->andReturn([]);

        $changeset = $this->documentComparator->getChangeset($this->dossier, $document, $metadata);
        self::assertFalse($changeset->hasChanges());
        self::assertFalse($changeset->isChanged(MetadataField::CASENR->value));

        self::assertFalse($this->documentComparator->needsUpdate($this->dossier, $document, $metadata));
    }

    public function testHasRefersToUpdateReturnsFalseWhenDocumentAndMetadataHaveNoReferrals(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());

        $metadata = Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn([]);

        self::assertFalse(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata),
        );
    }

    public function testHasRefersToUpdateReturnsTrueWhenAReferralIsAdded(): void
    {
        $this->dossier->expects('getDocumentPrefix')->times(3)->andReturn('prefix');

        $document = Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());
        $document->expects('getDocumentNr')->andReturn('bar-123');
        $document->expects('getDocumentId')->times(3)->andReturn('123');

        $metadata = Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn(['foo-123']);

        $referredDocument = Mockery::mock(Document::class);
        $referredDocument->expects('getDocumentNr')->andReturn('foo-123');

        $this->repository->expects('findByDocumentNumber')->andReturn($referredDocument);

        self::assertTrue(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata),
        );
    }

    public function testHasRefersToUpdateIgnoresInvalidReferral(): void
    {
        $this->dossier->expects('getDocumentPrefix')->times(6)->andReturn('prefix');

        $document = Mockery::mock(Document::class);
        $document->expects('getRefersTo')->andReturn(new ArrayCollection());
        $document->expects('getDocumentNr')->times(2)->andReturn('bar-123');
        $document->expects('getDocumentId')->times(6)->andReturn('123');

        $metadata = Mockery::mock(DocumentMetadata::class);
        $metadata->expects('getRefersTo')->andReturn(['foo-123', 'invalid-456']);

        $referredDocument = Mockery::mock(Document::class);
        $referredDocument->expects('getDocumentNr')->andReturn('foo-123');

        $this->repository->expects('findByDocumentNumber')->andReturn($referredDocument);
        $this->repository->expects('findByDocumentNumber')->andReturnNull();

        self::assertTrue(
            $this->documentComparator->hasRefersToUpdate($this->dossier, $document, $metadata),
        );
    }
}
