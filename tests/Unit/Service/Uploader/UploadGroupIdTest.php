<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;

final class UploadGroupIdTest extends UnitTestCase
{
    public function testGetFileTypes(): void
    {
        foreach (UploadGroupId::cases() as $uploadGroupId) {
            self::assertMatchesYamlSnapshot([
                'uploadGroupId' => $uploadGroupId->name,
                'types' => $uploadGroupId->getFileTypes(),
            ]);
        }
    }

    public function testGetExtensions(): void
    {
        foreach (UploadGroupId::cases() as $uploadGroupId) {
            self::assertMatchesYamlSnapshot([
                'uploadGroupId' => $uploadGroupId->name,
                'extensions' => $uploadGroupId->getExtensions(),
            ]);
        }
    }

    public function testGetMimeTypes(): void
    {
        foreach (UploadGroupId::cases() as $uploadGroupId) {
            self::assertMatchesYamlSnapshot([
                'uploadGroupId' => $uploadGroupId->name,
                'mimeTypes' => $uploadGroupId->getMimeTypes(),
            ]);
        }
    }

    public function testGetFileTypeNames(): void
    {
        foreach (UploadGroupId::cases() as $uploadGroupId) {
            self::assertMatchesYamlSnapshot([
                'uploadGroupId' => $uploadGroupId->name,
                'fileTypeNames' => $uploadGroupId->getFileTypeNames(),
            ]);
        }
    }
}
