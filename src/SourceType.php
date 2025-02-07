<?php

declare(strict_types=1);

namespace App;

use App\Domain\Upload\FileType\FileType;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum SourceType: string implements TranslatableInterface
{
    case PDF = 'pdf';
    case DOC = 'doc';
    case IMAGE = 'image';
    case PRESENTATION = 'presentation';
    case SPREADSHEET = 'spreadsheet';
    case EMAIL = 'email';
    case HTML = 'html';
    case NOTE = 'note';
    case DATABASE = 'database';
    case XML = 'xml';
    case VIDEO = 'video';
    case VCARD = 'vcard';
    case CHAT = 'chat';
    case UNKNOWN = 'unknown';

    // Finds the given source type in the list of known types
    public static function create(?string $target): self
    {
        if ($target === null) {
            return self::UNKNOWN;
        }

        $target = strtolower(trim($target));

        // First check for an exact match on enum cases
        $match = self::tryFrom($target);
        if ($match instanceof self) {
            return $match;
        }

        // If there is no match try matching on alternative values
        return match ($target) {
            'word processing',
            'application/vnd.openxmlformats-officedocument',
            'application/rms.encrypted.ms-office' => self::DOC,
            'application/msonenote' => self::NOTE,
            'application/x-sqlite3' => self::DATABASE,
            'chatbericht' => self::CHAT,
            default => self::UNKNOWN,
        };
    }

    public static function fromFileType(FileType $fileType): self
    {
        return match ($fileType) {
            FileType::PDF => self::PDF,
            FileType::DOC, FileType::TXT => self::DOC,
            FileType::XLS => self::SPREADSHEET,
            FileType::PPT => self::PRESENTATION,
            FileType::ZIP => self::UNKNOWN,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('public.documents.file_type.' . $this->value, locale: $locale);
    }

    public function isEmail(): bool
    {
        return $this === self::EMAIL;
    }
}
