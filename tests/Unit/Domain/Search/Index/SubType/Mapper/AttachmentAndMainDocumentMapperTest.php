<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Index\Dossier\DossierIndexer;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\SubType\Mapper\AttachmentAndMainDocumentMapper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class AttachmentAndMainDocumentMapperTest extends UnitTestCase
{
    private AttachmentAndMainDocumentMapper $mapper;
    private DossierIndexer&MockInterface $dossierIndexer;

    protected function setUp(): void
    {
        $this->dossierIndexer = Mockery::mock(DossierIndexer::class);
        $this->mapper = new AttachmentAndMainDocumentMapper($this->dossierIndexer);

        parent::setUp();
    }

    public function testMap(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn('PREFIX');
        $dossier->expects('getDossierNr')->andReturn('foo-123');
        $dossier->expects('getOrganisation->getId')
            ->andReturn(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'));

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getMimetype')->andReturn('text/plain');
        $fileInfo->expects('getSize')->andReturn(1234);
        $fileInfo->expects('getType')->andReturn('txt');
        $fileInfo->expects('getSourceType')->andReturn(SourceType::DOC);
        $fileInfo->expects('getName')->andReturn('foo.bar');

        $attachmentId = Uuid::v6();
        $attachment = Mockery::mock(WooDecisionAttachment::class);
        $attachment->expects('getId')->andReturn($attachmentId);
        $attachment->expects('getDossier')->times(4)->andReturn($dossier);
        $attachment->expects('getFileInfo')->andReturn($fileInfo);
        $attachment->expects('getGrounds')->andReturn(['x', 'y']);
        $attachment->expects('getFormalDate')->andReturn(PlainDate::create('2024-06-18'));

        $this->dossierIndexer->expects('map')->with($dossier)->andReturn(
            new ElasticDocument('foo-123', ElasticDocumentType::COVENANT, null, ['mapped-dossier-data' => 'dummy']),
        );

        $doc = $this->mapper->map($attachment, ['foo'], [1 => 'bar']);

        $this->assertMatchesJsonSnapshot($doc->getDocumentValues());
    }
}
