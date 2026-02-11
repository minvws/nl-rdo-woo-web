<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Service\DownloadFilenameGenerator;
use Shared\Tests\Unit\UnitTestCase;

class DownloadFilenameGeneratorTest extends UnitTestCase
{
    public function testGetFileNameForDocument(): void
    {
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getDocumentNr')->andReturn('123');
        $document->shouldReceive('getFileInfo->getType')->andReturn('csv');

        $generator = new DownloadFilenameGenerator();

        self::assertEquals(
            '123.csv',
            $generator->getFileName($document),
        );
    }

    public function testGetFileNameForAttachment(): void
    {
        $attachment = Mockery::mock(WooDecisionAttachment::class);
        $attachment->shouldReceive('getFileInfo->getName')->andReturn('foo-b@r.bla.docx');

        $generator = new DownloadFilenameGenerator();

        self::assertEquals(
            'foo-b_r.bla.docx',
            $generator->getFileName($attachment),
        );
    }
}
