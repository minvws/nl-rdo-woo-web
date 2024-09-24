<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Upload\Extractor;

use App\Domain\Upload\Extractor\Extractor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ExtractorTest extends KernelTestCase
{
    public function testGetFilesWithZipArchive(): void
    {
        /** @var Extractor $extractor */
        $extractor = self::getContainer()->get('extractor.zip');

        $file = new \SplFileInfo(__DIR__ . '/fixtures/Archive.zip');
        $result = $extractor->getFiles($file);

        $i = 0;
        $paths = [];
        foreach ($result as $file) {
            $i++;
            $paths[] = $path = $file->getPathname();

            $this->assertTrue(is_file($path), 'File is not extracted succesfully does not exist: ' . $path);
        }

        foreach ($paths as $path) {
            $this->assertFalse(is_file($path), 'File should not exist after initial iteration: ' . $path);
        }

        $this->assertEquals(3, $i, 'Expected 2 files to be extracted');
    }

    public function testGetFilesWithSevenZipArchive(): void
    {
        /** @var Extractor $extractor */
        $extractor = self::getContainer()->get('extractor.7z');

        $file = new \SplFileInfo(__DIR__ . '/fixtures/Archive.7z');
        $result = $extractor->getFiles($file);

        $i = 0;
        $paths = [];
        foreach ($result as $file) {
            $i++;
            $paths[] = $path = $file->getPathname();

            $this->assertTrue(is_file($path), 'File is not extracted succesfully does not exist: ' . $path);
        }

        foreach ($paths as $path) {
            $this->assertFalse(is_file($path), 'File should not exist after initial iteration: ' . $path);
        }

        $this->assertEquals(3, $i, 'Expected 2 files to be extracted');
    }
}
