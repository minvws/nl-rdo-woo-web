<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use App\Domain\Publication\SourceType;
use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\SubType\Mapper\WooDecisionDocumentMapper;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Uid\Uuid;

class WooDecisionDocumentMapperTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private WooDecisionDocumentMapper $mapper;
    private WooDecisionMapper&MockInterface $wooDecisionMapper;

    protected function setUp(): void
    {
        $this->wooDecisionMapper = \Mockery::mock(WooDecisionMapper::class);

        $this->mapper = new WooDecisionDocumentMapper($this->wooDecisionMapper);

        parent::setUp();
    }

    public function testSupportsReturnsTrueForDocument(): void
    {
        self::assertTrue(
            $this->mapper->supports(\Mockery::mock(Document::class))
        );
    }

    public function testSupportsReturnsFalseForAttachment(): void
    {
        self::assertFalse(
            $this->mapper->supports(\Mockery::mock(CovenantAttachment::class))
        );
    }

    public function testMap(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('PREFIX');
        $dossier->shouldReceive('getOrganisation->getId')->andReturn(
            Uuid::fromRfc4122('55ae5de9-55f4-3420-b40b-5cde6e07fc5a'),
        );

        $dossierDoc = \Mockery::mock(ElasticDocument::class);
        $dossierDoc->shouldReceive('getDocumentValues')->andReturn(['mapped-dossier-data' => 'dummy']);

        $this->wooDecisionMapper->expects('map')->with($dossier)->andReturn($dossierDoc);

        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getId')->andReturn(
            Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'),
        );

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->andReturn('application/pdf');
        $fileInfo->shouldReceive('getSize')->andReturn(1234);
        $fileInfo->shouldReceive('getType')->andReturn('pdf');
        $fileInfo->shouldReceive('getSourceType')->andReturn(SourceType::DOC);
        $fileInfo->shouldReceive('getName')->andReturn('foo.bar');
        $fileInfo->shouldReceive('getPageCount')->andReturn(13);

        $referredDocumentA = \Mockery::mock(Document::class);
        $referredDocumentA->shouldReceive('getDocumentNr')->andReturn('doc-456');

        $referredDocumentB = \Mockery::mock(Document::class);
        $referredDocumentB->shouldReceive('getDocumentNr')->andReturn('doc-789');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId->toRfc4122')->andReturn('doc-456');
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiry]));
        $document->shouldReceive('getReferredBy')->andReturn(new ArrayCollection([$referredDocumentA, $referredDocumentB]));
        $document->shouldReceive('getDocumentNr')->andReturn('doc-123');
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getDocumentDate')->andReturn(new \DateTimeImmutable('2024-04-16 10:54:15'));
        $document->shouldReceive('getFamilyId')->andReturn(789);
        $document->shouldReceive('getDocumentId')->andReturn('abc123');
        $document->shouldReceive('getThreadId')->andReturn(567);
        $document->shouldReceive('getJudgement')->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->shouldReceive('getGrounds')->andReturn(['x', 'y']);
        $document->shouldReceive('getPeriod')->andReturn('foo-bar');

        $this->assertMatchesSnapshot(
            $this->mapper->map($document, ['foo'], [1 => 'bar']),
        );
    }
}
