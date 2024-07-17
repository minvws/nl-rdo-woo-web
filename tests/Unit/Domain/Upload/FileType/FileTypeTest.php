<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileType;
use App\Tests\Unit\UnitTestCase;

final class FileTypeTest extends UnitTestCase
{
    public function testGetExtensionsForTypesWithSingleType(): void
    {
        self::assertEquals(
            ['txt', 'rdf'],
            FileType::getExtensionsForTypes(FileType::TXT),
        );
    }

    public function testGetExtensionsForTypesWithMultipleTypes(): void
    {
        self::assertEquals(
            ['doc', 'docx', 'odt', 'txt', 'rdf'],
            FileType::getExtensionsForTypes(FileType::DOC, FileType::TXT),
        );
    }
}
