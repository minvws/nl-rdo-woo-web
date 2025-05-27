<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;

class MimeTypeHelperTest extends MockeryTestCase
{
    private MimeTypeHelper $helper;

    public function setUp(): void
    {
        $this->helper = new MimeTypeHelper();

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

    public function testDetectMimetypeForBat(): void
    {
        $upload = \Mockery::mock(FilesystemFile::class);
        $upload->shouldReceive('getClientOriginalName')->andReturn('1234.bat');
        $upload->shouldReceive('getPathname')->andReturn(__DIR__ . '/invalid-upload.bat');

        self::assertEquals(
            $this->helper->detectMimeType($upload),
            'application/x-msdownload',
        );
    }

    public function testDetectMimetypeForCsv(): void
    {
        $upload = \Mockery::mock(FilesystemFile::class);
        $upload->shouldReceive('getClientOriginalName')->andReturn('1234.bat');
        $upload->shouldReceive('getPathname')->andReturn(__DIR__ . '/valid-upload-file.csv');

        self::assertEquals(
            $this->helper->detectMimeType($upload),
            'text/csv',
        );
    }
}
