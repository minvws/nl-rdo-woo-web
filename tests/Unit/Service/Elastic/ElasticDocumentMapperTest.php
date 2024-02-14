<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Elastic;

use App\Entity\Document;
use App\Entity\FileInfo;
use App\Entity\Judgement;
use App\Service\Elastic\ElasticDocumentMapper;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ElasticDocumentMapperTest extends MockeryTestCase
{
    private ElasticDocumentMapper $mapper;

    public function setUp(): void
    {
        $this->mapper = new ElasticDocumentMapper();

        parent::setUp();
    }

    public function testMapDocumentToElasticDocumentIncludesDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-10 10:11:12');
        $document = $this->generateDocument($date);

        $elasticData = $this->mapper->mapDocumentToElasticDocument($document);

        $this->assertEquals(
            $date->format(\DateTimeInterface::ATOM),
            $elasticData['date'],
        );
    }

    public function testMapDocumentToElasticDocumentNullDate(): void
    {
        $document = $this->generateDocument(null);

        $elasticData = $this->mapper->mapDocumentToElasticDocument($document);

        $this->assertEquals(
            null,
            $elasticData['date'],
        );
    }

    private function generateDocument(?\DateTimeImmutable $date): Document
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->andReturn('');
        $fileInfo->shouldReceive('getSize')->andReturn(0);
        $fileInfo->shouldReceive('getType')->andReturn('');
        $fileInfo->shouldReceive('getSourceType')->andReturn('');
        $fileInfo->shouldReceive('getName')->andReturn('');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection());
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection());
        $document->shouldReceive('getDocumentNr')->andReturn('123');
        $document->shouldReceive('getDocumentId')->andReturn('doc-123');
        $document->shouldReceive('getDocumentDate')->andReturn($date);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getFamilyId')->andReturnNull();
        $document->shouldReceive('getThreadId')->andReturnNull();
        $document->shouldReceive('getJudgement')->andReturn(Judgement::PUBLIC);
        $document->shouldReceive('getGrounds')->andReturn([]);
        $document->shouldReceive('getSubjects')->andReturn([]);
        $document->shouldReceive('getPeriod')->andReturnNull();
        $document->shouldReceive('getPageCount')->andReturn(0);

        return $document;
    }
}
