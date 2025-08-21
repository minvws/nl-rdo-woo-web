<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\UploadedFile;
use App\Service\Uploader\UploadGroupId;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class MimeTypeHelperTest extends MockeryTestCase
{
    private FinfoMimeTypeDetector&MockInterface $mimeTypeDetector;
    private MimeTypeHelper $helper;

    public function setUp(): void
    {
        $this->mimeTypeDetector = \Mockery::mock(FinfoMimeTypeDetector::class);
        $this->helper = new MimeTypeHelper($this->mimeTypeDetector);

        parent::setUp();
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeIsNotAllowedBasedOnFileContents(): void
    {
        self::assertFalse(
            $this->helper->isValidForUploadGroup('foo/bar', UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    public function testIsFileMimeTypeValidForUploadGroupReturnsTrueForValidMimeType(): void
    {
        self::assertTrue(
            $this->helper->isValidForUploadGroup('application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    public function testDetectMimetypeFromPath(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getOriginalFilename')->andReturn($filename = 'upload.bat');
        $upload->shouldReceive('getPathname')->andReturn('/var/www/foobar/upload.bat');

        $this->mimeTypeDetector
            ->shouldReceive('detectMimeTypeFromPath')
            ->with($filename)
            ->andReturn($mimeType = 'application/x-msdownload');

        self::assertEquals(
            $mimeType,
            $this->helper->detectMimeTypeFromPath($upload),
        );
    }

    public function testDetectMimetype(): void
    {
        $pathname = '/var/www/foobar/upload-file.csv';

        $this->mimeTypeDetector
            ->shouldReceive('detectMimeType')
            ->with($pathname, $contents = 'some contents')
            ->andReturn($mimeType = 'text/csv');

        self::assertEquals(
            $mimeType,
            $this->helper->detectMimeType($pathname, $contents),
        );
    }
}
