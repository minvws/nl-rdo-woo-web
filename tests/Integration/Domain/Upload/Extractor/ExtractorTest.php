<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Upload\Extractor;

use Shared\Domain\Upload\Extractor\Extractor;
use Shared\Tests\Integration\SharedWebTestCase;
use SplFileInfo;

use function is_file;

final class ExtractorTest extends SharedWebTestCase
{
    public function testGetFilesWithSevenZipArchiveGivenAZipFile(): void
    {
        /** @var Extractor $extractor */
        $extractor = self::getContainer()->get('extractor.7z');

        $file = new SplFileInfo(__DIR__ . '/fixtures/Archive.zip');
        $result = $extractor->getFiles($file);

        $i = 0;
        $paths = [];
        foreach ($result as $file) {
            $i++;
            $paths[] = $path = $file->getPathname();

            $this->assertTrue(is_file($path), 'File is not extracted succesfully: ' . $path);
        }

        foreach ($paths as $path) {
            $this->assertFalse(is_file($path), 'File should not exist after initial iteration: ' . $path);
        }

        $this->assertEquals(3, $i, 'Expected 2 files to be extracted');
    }

    public function testGetFilesWithSevenZipArchiveGivenA7ZipFile(): void
    {
        /** @var Extractor $extractor */
        $extractor = self::getContainer()->get('extractor.7z');

        $file = new SplFileInfo(__DIR__ . '/fixtures/Archive.7z');
        $result = $extractor->getFiles($file);

        $i = 0;
        $paths = [];
        foreach ($result as $file) {
            $i++;
            $paths[] = $path = $file->getPathname();

            $this->assertTrue(is_file($path), 'File is not extracted succesfully: ' . $path);
        }

        foreach ($paths as $path) {
            $this->assertFalse(is_file($path), 'File should not exist after initial iteration: ' . $path);
        }

        $this->assertEquals(3, $i, 'Expected 2 files to be extracted');
    }
}
