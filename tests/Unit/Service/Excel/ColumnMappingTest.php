<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Excel;

use App\Service\FileReader\ColumnMapping;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ColumnMappingTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $name = 'test-col';
        $aliases = ['test', 'test-col'];

        $columnMapping = new ColumnMapping(
            $name,
            true,
            $aliases,
        );

        $this->assertEquals($name, $columnMapping->getName());
        $this->assertTrue($columnMapping->isRequired());
        $this->assertEquals($aliases, $columnMapping->getColumnNames());
    }
}
