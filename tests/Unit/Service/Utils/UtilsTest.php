<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Utils\Utils;
use Shared\Tests\Unit\UnitTestCase;

class UtilsTest extends UnitTestCase
{
    #[DataProvider('getSizeProvider')]
    public function testGetSize(string|int $input, string $expected): void
    {
        self::assertEquals($expected, Utils::size($input));
    }

    /**
     * @return array<string,array{input:string|int,expected:string}>
     */
    public static function getSizeProvider(): array
    {
        return [
            'bytes-as-int' => [
                'input' => 456,
                'expected' => '456 bytes',
            ],
            'kilobytes-as-string' => [
                'input' => '456',
                'expected' => '456 bytes',
            ],
            'kilobytes-as-int' => [
                'input' => 45640,
                'expected' => '44.57 KB',
            ],
            'bytes-as-string' => [
                'input' => '45640',
                'expected' => '44.57 KB',
            ],
            'megabytes-as-int' => [
                'input' => 45640123,
                'expected' => '43.53 MB',
            ],
            'megabytes-as-string' => [
                'input' => '45640123',
                'expected' => '43.53 MB',
            ],
            'gigabytes-as-int' => [
                'input' => 45640123897,
                'expected' => '42.51 GB',
            ],
            'gigabytes-as-string' => [
                'input' => '45640123897',
                'expected' => '42.51 GB',
            ],
        ];
    }

    public function testGetFileSize(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->expects('getSize')->andReturn(12345);

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        self::assertEquals('12.06 KB', Utils::getFileSize($entity));
    }

    #[DataProvider('numberProvider')]
    public function testNumber(int $input, string $expected): void
    {
        self::assertEquals($expected, Utils::number($input));
    }

    /**
     * @return array<string,array{input:int,expected:string}>
     */
    public static function numberProvider(): array
    {
        return [
            'small number' => [
                'input' => 456,
                'expected' => '456',
            ],
            'large number' => [
                'input' => 1234567890,
                'expected' => '1.234.567.890',
            ],
            'zero' => [
                'input' => 0,
                'expected' => '0',
            ],
            'negative number' => [
                'input' => -4567890,
                'expected' => '-4.567.890',
            ],
        ];
    }
}
