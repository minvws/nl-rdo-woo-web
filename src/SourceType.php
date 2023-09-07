<?php

declare(strict_types=1);

namespace App;

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
    public const SOURCE_AUDIO = 'audio';
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
        self::SOURCE_AUDIO => [
            'audio',
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
    public static function getType(string $target): string
    {
        $target = strtolower($target);

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
        switch ($value) {
            case self::SOURCE_PDF:
                return 'fas fa-file-pdf';
            case self::SOURCE_DOCUMENT:
                return 'fas fa-file-word';
            case self::SOURCE_SPREADSHEET:
                return 'fas fa-file-excel';
            case self::SOURCE_EMAIL:
                return 'fas fa-envelope';
            case self::SOURCE_PRESENTATION:
                return 'fas fa-file-powerpoint';
            case self::SOURCE_UNKNOWN:
                return 'fas fa-file';
        }

        return 'fas fa-file';
    }
}
