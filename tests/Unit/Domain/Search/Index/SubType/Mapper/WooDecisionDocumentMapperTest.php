<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\SubType\Mapper\WooDecisionDocumentMapper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class WooDecisionDocumentMapperTest extends UnitTestCase
{
    private WooDecisionDocumentMapper $mapper;
    private WooDecisionMapper&MockInterface $wooDecisionMapper;

    protected function setUp(): void
    {
        $this->wooDecisionMapper = Mockery::mock(WooDecisionMapper::class);

        $this->mapper = new WooDecisionDocumentMapper($this->wooDecisionMapper);

        parent::setUp();
    }

    public function testSupportsReturnsTrueForDocument(): void
    {
        self::assertTrue(
            $this->mapper->supports(Mockery::mock(Document::class)),
        );
    }

    public function testSupportsReturnsFalseForAttachment(): void
    {
        self::assertFalse(
            $this->mapper->supports(Mockery::mock(CovenantAttachment::class)),
        );
    }

    public function testMap(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDossierNr')->andReturn('dos-123');
        $dossier->expects('getDocumentPrefix')->andReturn('PREFIX');
        $dossier->expects('getOrganisation->getId')->andReturn(
            Uuid::fromRfc4122('55ae5de9-55f4-3420-b40b-5cde6e07fc5a'),
        );

        $dossierDoc = Mockery::mock(ElasticDocument::class);
        $dossierDoc->expects('getDocumentValues')->andReturn(['mapped-dossier-data' => 'dummy']);

        $this->wooDecisionMapper->expects('map')->with($dossier)->andReturn($dossierDoc);

        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getId')->andReturn(
            Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'),
        );

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getMimetype')->andReturn('application/pdf');
        $fileInfo->expects('getSize')->andReturn(1234);
        $fileInfo->expects('getType')->andReturn('pdf');
        $fileInfo->expects('getSourceType')->andReturn(SourceType::DOC);
        $fileInfo->expects('getName')->andReturn('foo.bar');
        $fileInfo->expects('getPageCount')->andReturn(13);

        $referredDocumentA = Mockery::mock(Document::class);
        $referredDocumentA->expects('getDocumentNr')->andReturn('doc-456');

        $referredDocumentB = Mockery::mock(Document::class);
        $referredDocumentB->expects('getDocumentNr')->andReturn('doc-789');

        $document = Mockery::mock(Document::class);
        $document->expects('getId->toRfc4122')->andReturn('doc-456');
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection([$inquiry]));
        $document->expects('getReferredBy')->andReturn(new ArrayCollection([$referredDocumentA, $referredDocumentB]));
        $document->expects('getDocumentNr')->andReturn('doc-123');
        $document->expects('getFileInfo')->times(2)->andReturn($fileInfo);
        $document->expects('getDocumentDate')->andReturn(PlainDate::create('2024-04-16'));
        $document->expects('getFamilyId')->andReturn(789);
        $document->expects('getDocumentId')->andReturn(DocumentId::create('abc123'));
        $document->expects('getThreadId')->andReturn(567);
        $document->expects('getJudgement')->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->expects('getGrounds')->andReturn(['x', 'y']);
        $document->expects('getPeriod')->andReturn('foo-bar');

        $this->assertMatchesSnapshot(
            $this->mapper->map($document, ['foo'], [1 => 'bar']),
        );
    }
}
