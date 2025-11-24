<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Shared\Domain\Upload\FileType\MimeTypeHelper;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;

class MimeTypeHelperTest extends UnitTestCase
{
    private FinfoMimeTypeDetector&MockInterface $mimeTypeDetector;
    private MimeTypeHelper $helper;

    protected function setUp(): void
    {
        $this->mimeTypeDetector = \Mockery::mock(FinfoMimeTypeDetector::class);
        $this->helper = new MimeTypeHelper($this->mimeTypeDetector, new NullLogger());

        parent::setUp();
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeDoesNotMatchFileExtension(): void
    {
        self::assertFalse(
            $this->helper->isValidForUploadGroup('foo', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeIsNotAllowedBasedOnFileContents(): void
    {
        self::assertFalse(
            $this->helper->isValidForUploadGroup('foo', 'foo/bar', UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    public function testIsValidForUploadGroupReturnsFalseWhenMimeTypeDoesNotMatchExtension(): void
    {
        self::assertFalse(
            $this->helper->isValidForUploadGroup('xls', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    #[DataProvider('validMimeTypeExtensionDataProvider')]
    public function testIsFileMimeTypeValidForUploadGroupReturnsTrueForValidMimeType(
        string $fileExtension,
        string $mimeType,
    ): void {
        self::assertTrue(
            $this->helper->isValidForUploadGroup($fileExtension, $mimeType, UploadGroupId::WOO_DECISION_DOCUMENTS),
        );
    }

    /**
     * @return array<array{fileExtension: string, mimeType: string}>
     */
    public static function validMimeTypeExtensionDataProvider(): array
    {
        return [
            ['fileExtension' => 'pdf', 'mimeType' => 'application/pdf'],
            ['fileExtension' => 'xls', 'mimeType' => 'application/msexcel'],
            ['fileExtension' => 'xlsx', 'mimeType' => 'application/msexcel'],
        ];
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
