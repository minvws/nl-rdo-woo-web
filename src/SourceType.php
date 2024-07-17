<?php

declare(strict_types=1);

namespace App;

use App\Domain\Upload\FileType\FileType;

/**
 * Returns human-readable document source types or "unknown" when not known.
 */
class SourceType
{
    public const SOURCE_PDF = 'pdf';
    public const SOURCE_DOCUMENT = 'doc';
    public const SOURCE_IMAGE = 'image';
    public const SOURCE_PRESENTATION = 'presentation';
    public const SOURCE_SPREADSHEET = 'spreadsheet';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_HTML = 'html';
    public const SOURCE_NOTE = 'note';
    public const SOURCE_DATABASE = 'database';
    public const SOURCE_XML = 'xml';
    public const SOURCE_VIDEO = 'video';
    public const SOURCE_VCARD = 'vcard';
    public const SOURCE_UNKNOWN = 'unknown';

    /** @var array<string, string[]> */
    public static array $types = [
        self::SOURCE_PDF => [
            'pdf',
        ],
        self::SOURCE_DOCUMENT => [
            'word processing',
            'application/vnd.openxmlformats-officedocument',
            'application/rms.encrypted.ms-office',
        ],
        self::SOURCE_IMAGE => [
            'image',
        ],
        self::SOURCE_PRESENTATION => [
            'presentation',
        ],
        self::SOURCE_SPREADSHEET => [
            'spreadsheet',
        ],
        self::SOURCE_EMAIL => [
            'email',
        ],
        self::SOURCE_HTML => [
            'html',
        ],
        self::SOURCE_NOTE => [
            'application/msonenote',
        ],
        self::SOURCE_DATABASE => [
            'database',
            'application/x-sqlite3',
        ],
        self::SOURCE_XML => [
            'xml',
        ],
        self::SOURCE_VIDEO => [
            'video',
        ],
        self::SOURCE_VCARD => [
            'vcard',
        ],
    ];

    // Finds the given source type in the list of known types
    public static function getType(?string $target): string
    {
        if ($target === null) {
            return self::SOURCE_UNKNOWN;
        }

        $target = strtolower(trim($target));

        foreach (self::$types as $type => $format) {
            if (in_array($target, $format)) {
                return $type;
            }
        }

        return self::SOURCE_UNKNOWN;
    }

    /**
     * Returns a list of all known source types.
     *
     * @return array|string[]
     */
    public static function getAllSourceTypes(): array
    {
        return array_keys(self::$types);
    }

    public static function getIcon(string $value): string
    {
        return match ($value) {
            self::SOURCE_PDF => 'fas fa-file-pdf',
            self::SOURCE_DOCUMENT => 'fas fa-file-word',
            self::SOURCE_SPREADSHEET => 'fas fa-file-excel',
            self::SOURCE_EMAIL => 'fas fa-envelope',
            self::SOURCE_PRESENTATION => 'fas fa-file-powerpoint',
            default => 'fas fa-file',
        };
    }

    public static function fromFileType(FileType $fileType): string
    {
        return match ($fileType) {
            FileType::PDF => self::SOURCE_PDF,
            FileType::DOC, FileType::TXT => self::SOURCE_DOCUMENT,
            FileType::XLS => self::SOURCE_SPREADSHEET,
            FileType::PPT => self::SOURCE_PRESENTATION,
            FileType::ZIP => self::SOURCE_UNKNOWN,
        };
    }
}
