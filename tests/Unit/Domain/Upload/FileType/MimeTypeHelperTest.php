<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Shared\Domain\Upload\FileType\MimeTypeHelper;
use Shared\Domain\Upload\FileType\MimeTypeHelperResult;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;

class MimeTypeHelperTest extends UnitTestCase
{
    private FinfoMimeTypeDetector&MockInterface $mimeTypeDetector;
    private MimeTypeHelper $helper;

    protected function setUp(): void
    {
        $this->mimeTypeDetector = Mockery::mock(FinfoMimeTypeDetector::class);
        $this->helper = new MimeTypeHelper($this->mimeTypeDetector, new NullLogger());

        parent::setUp();
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeDoesNotMatchFileExtension(): void
    {
        self::assertSame(
            $this->helper->isValidForUploadGroup('foo', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS),
            MimeTypeHelperResult::INVALID_EXTENSION,
        );
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeIsNotAllowedBasedOnFileContents(): void
    {
        self::assertSame(
            $this->helper->isValidForUploadGroup('pdf', 'foo/bar', UploadGroupId::WOO_DECISION_DOCUMENTS),
            MimeTypeHelperResult::INVALID_MIME_TYPE,
        );
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeDoesNotMatchExtension(): void
    {
        self::assertSame(
            $this->helper->isValidForUploadGroup('xls', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS),
            MimeTypeHelperResult::MISMATCH_BETWEEN_EXTENSION_AND_MIME_TYPE,
        );
    }

    #[DataProvider('validMimeTypeExtensionDataProvider')]
    public function testIsFileMimeTypeValidForUploadGroupReturnsTrueForValidMimeType(
        string $fileExtension,
        string $mimeType,
    ): void {
        self::assertSame(
            MimeTypeHelperResult::VALID,
            $this->helper->isValidForUploadGroup($fileExtension, $mimeType, UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    /**
     * @return array<array-key, array{fileExtension: string, mimeType: string}>
     */
    public static function validMimeTypeExtensionDataProvider(): array
    {
        return [
            ['fileExtension' => 'pdf', 'mimeType' => 'application/pdf'],
            ['fileExtension' => 'PDF', 'mimeType' => 'application/pdf'],
            ['fileExtension' => 'xls', 'mimeType' => 'application/msexcel'],
            ['fileExtension' => 'Xls', 'mimeType' => 'application/msexcel'],
            ['fileExtension' => 'xlsx', 'mimeType' => 'application/msexcel'],
            ['fileExtension' => 'xlsX', 'mimeType' => 'application/msexcel'],
        ];
    }

    public function testDetectMimetypeFromPath(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $upload->expects('getOriginalFilename')->andReturn($filename = 'upload.bat');

        $this->mimeTypeDetector
            ->expects('detectMimeTypeFromPath')
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
            ->expects('detectMimeType')
            ->with($pathname, $contents = 'some contents')
            ->andReturn($mimeType = 'text/csv');

        self::assertEquals(
            $mimeType,
            $this->helper->detectMimeType($pathname, $contents),
        );
    }
}
