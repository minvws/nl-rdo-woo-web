<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Service\DownloadFilenameGenerator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DownloadFilenameGeneratorTest extends MockeryTestCase
{
    public function testGetFileNameForDocument(): void
    {
        $document = \Mockery::mock(Document::class);
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
        $attachment = \Mockery::mock(WooDecisionAttachment::class);
        $attachment->shouldReceive('getFileInfo->getName')->andReturn('foo-b@r.bla.docx');

        $generator = new DownloadFilenameGenerator();

        self::assertEquals(
            'foo-b_r.bla.docx',
            $generator->getFileName($attachment),
        );
    }
}
