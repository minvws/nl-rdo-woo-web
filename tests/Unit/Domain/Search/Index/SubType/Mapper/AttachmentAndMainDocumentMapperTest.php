<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Index\Dossier\DossierIndexer;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\SubType\Mapper\AttachmentAndMainDocumentMapper;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class AttachmentAndMainDocumentMapperTest extends UnitTestCase
{
    private AttachmentAndMainDocumentMapper $mapper;
    private DossierIndexer&MockInterface $dossierIndexer;

    protected function setUp(): void
    {
        $this->dossierIndexer = \Mockery::mock(DossierIndexer::class);
        $this->mapper = new AttachmentAndMainDocumentMapper($this->dossierIndexer);

        parent::setUp();
    }

    public function testMap(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('PREFIX');
        $dossier->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);
        $dossier->shouldReceive('getOrganisation->getId')
            ->andReturn(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'));

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->andReturn('text/plain');
        $fileInfo->shouldReceive('getSize')->andReturn(1234);
        $fileInfo->shouldReceive('getType')->andReturn('txt');
        $fileInfo->shouldReceive('getSourceType')->andReturn(SourceType::DOC);
        $fileInfo->shouldReceive('getName')->andReturn('foo.bar');

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(WooDecisionAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentId);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getGrounds')->andReturn(['x', 'y']);
        $attachment->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable('2024-06-18 19:31:12'));

        $this->dossierIndexer->shouldReceive('map')->with($dossier)->andReturn(
            new ElasticDocument('foo-123', ElasticDocumentType::COVENANT, null, ['mapped-dossier-data' => 'dummy'])
        );

        $doc = $this->mapper->map($attachment, ['foo'], [1 => 'bar']);

        $this->assertMatchesJsonSnapshot($doc->getDocumentValues());
    }
}
