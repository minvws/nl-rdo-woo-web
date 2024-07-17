<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Dossier\DossierIndexer;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\SubType\Mapper\AttachmentAndMainDocumentMapper;
use App\Entity\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class AttachmentAndMainDocumentMapperTest extends MockeryTestCase
{
    private AttachmentAndMainDocumentMapper $mapper;
    private DossierIndexer&MockInterface $dossierIndexer;

    public function setUp(): void
    {
        $this->dossierIndexer = \Mockery::mock(DossierIndexer::class);
        $this->mapper = new AttachmentAndMainDocumentMapper($this->dossierIndexer);

        parent::setUp();
    }

    public function testMap(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->andReturn('application/pdf');
        $fileInfo->shouldReceive('getSize')->andReturn(1234);
        $fileInfo->shouldReceive('getType')->andReturn('pdf');
        $fileInfo->shouldReceive('getSourceType')->andReturn('doc');
        $fileInfo->shouldReceive('getName')->andReturn('foo.bar');

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentId);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getGrounds')->andReturn(['x', 'y']);
        $attachment->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable('2024-06-18 19:31:12'));

        $this->dossierIndexer->shouldReceive('map')->with($dossier)->andReturn(
            new ElasticDocument('foo-123', ElasticDocumentType::COVENANT, null, ['mapped-dossier-data' => 'dummy'])
        );

        $doc = $this->mapper->map($attachment);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::ATTACHMENT,
                'toplevel_type' => ElasticDocumentType::COVENANT,
                'sublevel_type' => ElasticDocumentType::ATTACHMENT,
                'mime_type' => 'application/pdf',
                'file_size' => 1234,
                'file_type' => 'pdf',
                'date' => '2024-06-18T19:31:12+00:00',
                'filename' => 'foo.bar',
                'grounds' => [
                    0 => 'x',
                    1 => 'y',
                ],
                'dossiers' => [
                    0 => [
                        'mapped-dossier-data' => 'dummy',
                        'type' => ElasticDocumentType::COVENANT,
                        'toplevel_type' => ElasticDocumentType::COVENANT,
                        'sublevel_type' => null,
                    ],
                ],
                'dossier_nr' => ['foo-123'],
            ],
            $doc->getDocumentValues(),
        );
    }
}
