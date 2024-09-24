<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

enum FileType: string
{
    case PDF = 'pdf';
    case XLS = 'xls';
    case DOC = 'doc';
    case TXT = 'txt';
    case PPT = 'ppt';
    case ZIP = 'zip';

    /**
     * @return string[]
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
        };
    }

    /**
     * @return string[]
     */
    public static function getExtensionsForTypes(self ...$types): array
    {
        $extensions = [];
        foreach ($types as $type) {
            $extensions = array_merge($extensions, $type->getExtensions());
        }

        return $extensions;
    }

    /**
     * @return list<string>
     */
    public static function getTypeNamesForTypes(self ...$types): array
    {
        $names = [];
        foreach ($types as $type) {
            $names[] = $type->getTypeName();
        }

        $names = array_values(array_unique($names));

        sort($names);

        return $names;
    }
}
