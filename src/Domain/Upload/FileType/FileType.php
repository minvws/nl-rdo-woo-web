<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use Symfony\Component\Mime\MimeTypes;

enum FileType: string
{
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

    /**
     * @return list<string>
     */
    public function getExtensions(): array
    {
        return match ($this) {
            self::PDF => ['pdf'],
            self::XLS => ['xls', 'xlsx', 'ods', 'odf', 'csv'],
            self::DOC => ['doc', 'docx', 'odt'],
            self::TXT => ['txt', 'rdf'],
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
            self::TXT => 'Word',
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

        $mimeTypes = [];
        foreach ($this->getExtensions() as $ext) {
            $mimeTypes = array_merge($mimeTypes, $mimeTypeGuesser->getMimeTypes($ext));
        }

        return array_values(array_unique($mimeTypes));
    }
}
