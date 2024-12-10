<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileType;
use App\Tests\Unit\UnitTestCase;

final class FileTypeTest extends UnitTestCase
{
    public function testGetExtensions(): void
    {
        $extensions = [];
        foreach (FileType::cases() as $fileType) {
            $extensions[$fileType->name] = $fileType->getExtensions();
        }

        self::assertMatchesYamlSnapshot($extensions);
    }

    public function testGetTypeName(): void
    {
        $typeNames = [];
        foreach (FileType::cases() as $fileType) {
            $typeNames[$fileType->name] = $fileType->getTypeName();
        }

        self::assertMatchesYamlSnapshot($typeNames);
    }

    public function testGetMimeTypes(): void
    {
        foreach (FileType::cases() as $fileType) {
            self::assertMatchesYamlSnapshot([
                'fileType' => $fileType->getTypeName(),
                'mimes' => $fileType->getMimeTypes(),
            ]);
        }
    }
}
