<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use Shared\Exception\FileReaderException;
use Shared\Service\FileReader\HeaderMap;
use Shared\Tests\Unit\UnitTestCase;

class HeaderMapTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $headerMap = new HeaderMap([
            'id' => 'A',
            'subject' => 'B',
        ]);

        self::assertEquals('A', $headerMap->getCellCoordinate('id'));

        self::assertTrue($headerMap->has('subject'));

        self::assertFalse($headerMap->has('foobar'));

        $this->expectExceptionObject(FileReaderException::forUnknownHeader('foobar'));
        $headerMap->getCellCoordinate('foobar');
    }
}
