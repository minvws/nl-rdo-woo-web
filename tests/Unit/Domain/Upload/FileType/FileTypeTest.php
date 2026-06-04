<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use PHPUnit\Framework\Attributes\DataProvider;
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
    public function testFromExtension(string $extension, ?FileType $expected): void
    {
        $result = FileType::fromExtension($extension);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<array-key, array{extension: string, expected: ?FileType}>
     */
    public static function fromExtensionDataProvider(): array
    {
        return [
            'pdf extension' => ['extension' => 'pdf', 'expected' => FileType::PDF],
            'xls extension' => ['extension' => 'xls', 'expected' => FileType::XLS],
            'xlsx extension' => ['extension' => 'xlsx', 'expected' => FileType::XLS],
            'doc extension' => ['extension' => 'doc', 'expected' => FileType::DOC],
            'docx extension' => ['extension' => 'docx', 'expected' => FileType::DOC],
            'txt extension' => ['extension' => 'txt', 'expected' => FileType::TXT],
            'ppt extension' => ['extension' => 'ppt', 'expected' => FileType::PPT],
            'zip extension' => ['extension' => 'zip', 'expected' => FileType::ZIP],
            '7z extension' => ['extension' => '7z', 'expected' => FileType::ZIP],
            'mp3 extension' => ['extension' => 'mp3', 'expected' => FileType::AUDIO],
            'mp4 extension' => ['extension' => 'mp4', 'expected' => FileType::VIDEO],
            'svg extension' => ['extension' => 'svg', 'expected' => FileType::VECTOR_IMAGE],
            'empty extension' => ['extension' => '', 'expected' => null],
            'unknown extension' => ['extension' => 'unknown', 'expected' => null],
        ];
    }
}
