<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\FileName;

use function str_repeat;

class FileNameTest extends UnitTestCase
{
    public function testCreateWithSimpleFilename(): void
    {
        $filename = FileName::create('document.pdf');

        $this->assertEquals('document.pdf', $filename->toString());
    }

    public function testCreateWithSpacesInName(): void
    {
        $filename = FileName::create('my document.pdf');

        $this->assertEquals('my document.pdf', $filename->toString());
    }

    public function testGetExtension(): void
    {
        $this->assertEquals('pdf', FileName::create('document.pdf')->getExtension());
    }

    public function testGetExtensionWithoutExtension(): void
    {
        $this->assertEquals('', FileName::create('filename without extension')->getExtension());
    }

    #[DataProvider('invalidFilenameDataProvider')]
    public function testCreateWithInvalidFilename(string $filename): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileName::create($filename);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidFilenameDataProvider(): array
    {
        return [
            'empty string' => [''],
            'path traversal' => ['../secret.txt'],
            'forward slash' => ['dir/file.txt'],
            'backslash' => ['dir\\file.txt'],
            'null byte' => ["file\0.txt"],
            'angle brackets' => ['<script>.txt'],
            'ampersand' => ['foo&bar.txt'],
            'hash' => ['file#1.txt'],
            'too long (> 255 chars)' => [str_repeat('a', 252) . '.txt'],
        ];
    }
}
