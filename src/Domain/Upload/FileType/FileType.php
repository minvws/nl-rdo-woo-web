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
            self::XLS => ['xls', 'xlsx', 'ods', 'odf'],
            self::DOC => ['doc', 'docx', 'odt'],
            self::TXT => ['txt', 'rdf'],
            self::PPT => ['pps', 'ppsx', 'ppt', 'pptx', 'odp'],
            self::ZIP => ['zip', '7z'],
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
}
