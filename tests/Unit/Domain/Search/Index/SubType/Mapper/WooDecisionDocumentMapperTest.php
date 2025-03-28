<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\SubType\Mapper\WooDecisionDocumentMapper;
use App\SourceType;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionDocumentMapperTest extends MockeryTestCase
{
    private WooDecisionDocumentMapper $mapper;
    private WooDecisionMapper&MockInterface $wooDecisionMapper;

    public function setUp(): void
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

        $dossierDoc = \Mockery::mock(ElasticDocument::class);
        $dossierDoc->shouldReceive('getDocumentValues')->andReturn(['mapped-dossier-data' => 'dummy']);

        $this->wooDecisionMapper->expects('map')->with($dossier)->andReturn($dossierDoc);

        $inquiryId = Uuid::v6();
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getId')->andReturn($inquiryId);

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->andReturn('application/pdf');
        $fileInfo->shouldReceive('getSize')->andReturn(1234);
        $fileInfo->shouldReceive('getType')->andReturn('pdf');
        $fileInfo->shouldReceive('getSourceType')->andReturn(SourceType::DOC);
        $fileInfo->shouldReceive('getName')->andReturn('foo.bar');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId->toRfc4122')->andReturn($documentId = 'doc-456');
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiry]));
        $document->shouldReceive('getDocumentNr')->andReturn('doc-123');
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getDocumentDate')->andReturn(new \DateTimeImmutable('2024-04-16 10:54:15'));
        $document->shouldReceive('getFamilyId')->andReturn(789);
        $document->shouldReceive('getDocumentId')->andReturn('abc123');
        $document->shouldReceive('getThreadId')->andReturn(567);
        $document->shouldReceive('getJudgement')->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->shouldReceive('getGrounds')->andReturn(['x', 'y']);
        $document->shouldReceive('getPeriod')->andReturn('foo-bar');
        $document->shouldReceive('getPageCount')->andReturn(13);

        $doc = $this->mapper->map($document, ['foo'], [1 => 'bar']);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::WOO_DECISION_DOCUMENT,
                'toplevel_type' => ElasticDocumentType::WOO_DECISION,
                'sublevel_type' => ElasticDocumentType::WOO_DECISION_DOCUMENT,
                'document_nr' => 'doc-123',
                'prefixed_dossier_nr' => [
                    'PREFIX|dos-123',
                ],
                'mime_type' => 'application/pdf',
                'file_size' => 1234,
                'file_type' => 'pdf',
                'source_type' => SourceType::DOC,
                'date' => '2024-04-16T10:54:15+00:00',
                'filename' => 'foo.bar',
                'family_id' => 789,
                'document_id' => 'abc123',
                'thread_id' => 567,
                'judgement' => Judgement::PARTIAL_PUBLIC,
                'grounds' => [
                    0 => 'x',
                    1 => 'y',
                ],
                'date_period' => 'foo-bar',
                'document_pages' => 13,
                'dossiers' => [
                    0 => ['mapped-dossier-data' => 'dummy'],
                ],
                'inquiry_ids' => [
                    $inquiryId,
                ],
                'metadata' => ['foo'],
                'pages' => [1 => 'bar'],
            ],
            $doc->getDocumentValues(),
        );
    }
}
