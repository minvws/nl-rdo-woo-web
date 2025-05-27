<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use org\bovigo\vfs\vfsStream;

trait TestFileTrait
{
    protected function createPdfTestFile(): void
    {
        vfsStream::newFile('test_file.pdf')
            ->withContent(file_get_contents(__DIR__ . '/test_file.pdf'))
            ->at($this->root);
    }
}
