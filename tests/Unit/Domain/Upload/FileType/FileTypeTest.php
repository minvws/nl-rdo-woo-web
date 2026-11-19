<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Tests\Unit\UnitTestCase;

final class FileTypeTest extends UnitTestCase
{
    public function testFileType(): void
    {
        $this->assertMatchesObjectSnapshot(FileType::cases());
    }

    #[DataProvider('fromMimeTypeDataProvider')]
    public function testFromMimeType(string $mimeType, ?FileType $expected): void
    {
        $result = FileType::fromMimeType($mimeType);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<array-key, array{mimeType: string, expected: ?FileType}>
     */
    public static function fromMimeTypeDataProvider(): array
    {
        return [
            'pdf mime type' => ['mimeType' => 'application/pdf', 'expected' => FileType::PDF],
            'excel mime type' => ['mimeType' => 'application/msexcel', 'expected' => FileType::XLS],
            'word mime type' => ['mimeType' => 'application/msword', 'expected' => FileType::DOC],
            'text mime type' => ['mimeType' => 'text/plain', 'expected' => FileType::TXT],
            'powerpoint mime type' => ['mimeType' => 'application/vnd.ms-powerpoint', 'expected' => FileType::PPT],
            'zip mime type' => ['mimeType' => 'application/zip', 'expected' => FileType::ZIP],
            'audio mime type' => ['mimeType' => 'audio/mpeg', 'expected' => FileType::AUDIO],
            'video mime type' => ['mimeType' => 'video/mp4', 'expected' => FileType::VIDEO],
            'svg mime type' => ['mimeType' => 'image/svg+xml', 'expected' => FileType::VECTOR_IMAGE],
            'empty mime type' => ['mimeType' => '', 'expected' => null],
            'unknown mime type' => ['mimeType' => 'application/unknown', 'expected' => null],
        ];
    }

    #[DataProvider('fromExtensionDataProvider')]
    public function testFromExtension(FileExtension $fileExtension, FileType $expected): void
    {
        $result = FileType::fromExtension($fileExtension);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<array-key, array{fileExtension: FileExtension, expected: FileType}>
     */
    public static function fromExtensionDataProvider(): array
    {
        return [
            'pdf extension' => ['fileExtension' => FileExtension::PDF, 'expected' => FileType::PDF],
            'xls extension' => ['fileExtension' => FileExtension::XLS, 'expected' => FileType::XLS],
            'xlsx extension' => ['fileExtension' => FileExtension::XLSX, 'expected' => FileType::XLS],
            'doc extension' => ['fileExtension' => FileExtension::DOC, 'expected' => FileType::DOC],
            'docx extension' => ['fileExtension' => FileExtension::DOCX, 'expected' => FileType::DOC],
            'txt extension' => ['fileExtension' => FileExtension::TXT, 'expected' => FileType::TXT],
            'ppt extension' => ['fileExtension' => FileExtension::PPT, 'expected' => FileType::PPT],
            'zip extension' => ['fileExtension' => FileExtension::ZIP, 'expected' => FileType::ZIP],
            '7z extension' => ['fileExtension' => FileExtension::SEVEN_ZIP, 'expected' => FileType::ZIP],
            'mp3 extension' => ['fileExtension' => FileExtension::MP3, 'expected' => FileType::AUDIO],
            'mp4 extension' => ['fileExtension' => FileExtension::MP4, 'expected' => FileType::VIDEO],
            'svg extension' => ['fileExtension' => FileExtension::SVG, 'expected' => FileType::VECTOR_IMAGE],
        ];
    }
}
