<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Excel;

use App\Exception\ExcelReaderException;
use App\Service\Excel\HeaderMap;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class HeaderMapTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $headerMap = new HeaderMap([
            'id' => 'A',
            'subject' => 'B',
        ]);

        $this->assertEquals('A', $headerMap->getCellCoordinate('id'));

        $this->assertTrue($headerMap->has('subject'));

        $this->assertFalse($headerMap->has('foobar'));

        $this->expectExceptionObject(ExcelReaderException::forUnknownHeader('foobar'));
        $headerMap->getCellCoordinate('foobar');
    }
}
