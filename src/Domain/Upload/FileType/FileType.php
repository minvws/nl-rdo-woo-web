<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\FileType;

use Symfony\Component\Mime\MimeTypes;

use function array_merge;
use function array_unique;
use function array_values;
use function in_array;

enum FileType: string
{
    /**
     * Constants for file size limits in bytes.
     */
    private const int MB = 1024 * 1024; // 1 MiB in bytes
    private const int GB = 1024 * self::MB; // 1 GiB in bytes

    case PDF = 'pdf';
    case XLS = 'xls';
    case DOC = 'doc';
    case TXT = 'txt';
    case PPT = 'ppt';
    case ZIP = 'zip';
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case VECTOR_IMAGE = 'vector-image';

    public static function fromMimeType(string $mimeType): ?self
    {
        if ($mimeType === '') {
            return null;
        }

        foreach (self::cases() as $fileType) {
            if (in_array($mimeType, $fileType->getMimeTypes(), true)) {
                return $fileType;
            }
        }

        return null;
    }

    public function getMaxUploadSize(): int
    {
        return match ($this) {
            self::ZIP => 4 * self::GB,
            self::VECTOR_IMAGE => 100 * self::MB,
            default => self::GB,
        };
    }

    /**
     * @return list<string>
     */
    public function getExtensions(): array
    {
        return match ($this) {
            self::PDF => ['pdf'],
            self::XLS => ['xls', 'xlsx', 'ods', 'odf', 'csv'],
            self::DOC => ['doc', 'docx', 'odt'],
            self::TXT => ['txt', 'rtf'],
            self::PPT => ['pps', 'ppsx', 'ppt', 'pptx', 'odp'],
            self::ZIP => ['zip', '7z'],
            self::AUDIO => ['aac', 'mp3', 'wav', 'opus'],
            self::VIDEO => ['wmv', 'avi', 'mp4', 'mov', 'm4a', 'mpg', 'asf'],
            self::VECTOR_IMAGE => ['svg'],
        };
    }

    public function getTypeName(): string
    {
        return match ($this) {
            self::PDF => 'PDF',
            self::XLS => 'Excel',
            self::DOC => 'Word',
            self::TXT => 'Text',
            self::PPT => 'PowerPoint',
            self::ZIP => 'Zip',
            self::AUDIO => 'Audio',
            self::VIDEO => 'Video',
            self::VECTOR_IMAGE => 'Vector Image',
        };
    }

    /**
     * @return list<string>
     */
    public function getMimeTypes(): array
    {
        $mimeTypeGuesser = MimeTypes::getDefault();

        $mimeTypes = $this->getExtraMimeTypes();
        foreach ($this->getExtensions() as $ext) {
            $mimeTypes = array_merge($mimeTypes, $mimeTypeGuesser->getMimeTypes($ext));
        }

        return array_values(array_unique($mimeTypes));
    }

    /**
     * @return list<string>
     */
    private function getExtraMimeTypes(): array
    {
        return match ($this) {
            // We added these mime types to the txt file type because the mime type guesser is wrong sometimes.
            self::TXT => ['application/x-ndjason', 'application/x-ndjson', 'application/ndjson'],
            default => [],
        };
    }
}
